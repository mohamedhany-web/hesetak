/**
 * فصول حصتك — in-meeting curriculum presenter (host picker + shared stage).
 * Syncs via HTTP Cache polling. Optional LiveKit DataChannel hooks remain no-ops if absent.
 * window.MxClassroomCurriculumPresenter.attach(api, config)
 */
(function (global) {
  'use strict';

  function $(id) {
    return document.getElementById(id);
  }

  function clamp(n, min, max) {
    return Math.max(min, Math.min(max, n));
  }

  function csrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.content : '';
  }

  function appendToken(url, token) {
    if (!url || !token) return url;
    var sep = url.indexOf('?') >= 0 ? '&' : '?';
    return url + sep + 'token=' + encodeURIComponent(token);
  }

  function isHostIdentity(identity) {
    return typeof identity === 'string' && identity.indexOf('host-') === 0;
  }

  function create(tag, cls, html) {
    var el = document.createElement(tag);
    if (cls) el.className = cls;
    if (html != null) el.innerHTML = html;
    return el;
  }

  function Presenter(lkApi, config) {
    this.api = lkApi;
    this.config = config || {};
    this.isHost = !!this.config.isHost;
    this.sessionId = null;
    this.slides = [];
    this.slideCount = 0;
    this.index = 1;
    this.scale = 1;
    this.tx = 0;
    this.ty = 0;
    this.laserOn = false;
    this.title = '';
    this.active = false;
    this._viewportTimer = null;
    this._pointerLast = 0;
    this._destroyed = false;
    this._catalogLoaded = false;
    this._pollTimer = null;
    this._pollInFlight = false;

    this.picker = null;
    this.stage = null;
    this.img = null;
    this.thumbs = null;
    this.counter = null;
    this.laserDot = null;
    this.titleEl = null;

    this._buildUi();
    this._bindSync();
  }

  Presenter.prototype._buildUi = function () {
    if (this.isHost) {
      this._ensurePicker();
      var btn = $('mx-ml-btn-curriculum');
      if (btn) {
        var self = this;
        btn.addEventListener('click', function () {
          if (self.active) {
            self.closeFromHost();
          } else {
            self.openPicker();
          }
        });
      }
    }
    this._ensureStage();
  };

  Presenter.prototype._ensurePicker = function () {
    if ($('mx-curriculum-picker')) {
      this.picker = $('mx-curriculum-picker');
      return;
    }
    var el = create('div', 'mx-curriculum-picker');
    el.id = 'mx-curriculum-picker';
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-modal', 'true');
    el.setAttribute('aria-hidden', 'true');
    el.innerHTML =
      '<div class="mx-curriculum-picker-card">' +
      '<div class="mx-curriculum-picker-head">' +
      '<strong>عرض منهج في الاجتماع</strong>' +
      '<button type="button" class="mx-curriculum-picker-close" aria-label="إغلاق">&times;</button>' +
      '</div>' +
      '<p class="mx-curriculum-picker-hint">اختر عرضاً جاهزاً (شرائح محوّلة). لا يتم مشاركة ملف PPTX الأصلي.</p>' +
      '<div class="mx-curriculum-picker-search"><input type="search" placeholder="بحث…" id="mx-curriculum-picker-q"></div>' +
      '<div class="mx-curriculum-picker-list" id="mx-curriculum-picker-list"><div class="mx-curriculum-picker-empty">جاري التحميل…</div></div>' +
      '</div>';
    document.body.appendChild(el);
    this.picker = el;
    var self = this;
    el.querySelector('.mx-curriculum-picker-close').addEventListener('click', function () {
      self.closePicker();
    });
    el.addEventListener('click', function (ev) {
      if (ev.target === el) self.closePicker();
    });
    var q = $('mx-curriculum-picker-q');
    if (q) {
      q.addEventListener('input', function () {
        self._filterCatalog(q.value);
      });
    }
  };

  Presenter.prototype._ensureStage = function () {
    if ($('mx-curriculum-stage')) {
      this.stage = $('mx-curriculum-stage');
      this.img = this.stage.querySelector('[data-mx-curr-img]');
      this.thumbs = this.stage.querySelector('[data-mx-curr-thumbs]');
      this.counter = this.stage.querySelector('[data-mx-curr-counter]');
      this.laserDot = this.stage.querySelector('[data-mx-curr-laser]');
      this.titleEl = this.stage.querySelector('[data-mx-curr-title]');
      return;
    }

    var hostChrome = this.isHost
      ? '<div class="mx-curriculum-stage-bar" data-mx-curr-host-bar>' +
        '<button type="button" data-mx-curr-prev title="السابق"><i class="fas fa-chevron-right"></i></button>' +
        '<span data-mx-curr-counter>1 / 1</span>' +
        '<button type="button" data-mx-curr-next title="التالي"><i class="fas fa-chevron-left"></i></button>' +
        '<span class="mx-curriculum-stage-sep"></span>' +
        '<button type="button" data-mx-curr-zoom-out title="تصغير"><i class="fas fa-search-minus"></i></button>' +
        '<button type="button" data-mx-curr-zoom-reset title="إعادة الزوم">100%</button>' +
        '<button type="button" data-mx-curr-zoom-in title="تكبير"><i class="fas fa-search-plus"></i></button>' +
        '<button type="button" data-mx-curr-laser title="ليزر"><i class="fas fa-location-crosshairs"></i></button>' +
        '<button type="button" data-mx-curr-fs title="ملء الشاشة"><i class="fas fa-expand"></i></button>' +
        '<button type="button" data-mx-curr-close class="is-danger" title="إغلاق العرض"><i class="fas fa-times"></i></button>' +
        '</div>'
      : '<div class="mx-curriculum-stage-bar is-guest">' +
        '<span data-mx-curr-title></span>' +
        '<span data-mx-curr-counter>1 / 1</span>' +
        '</div>';

    var el = create('div', 'mx-curriculum-stage');
    el.id = 'mx-curriculum-stage';
    el.setAttribute('aria-hidden', 'true');
    el.innerHTML =
      '<div class="mx-curriculum-stage-inner">' +
      '<div class="mx-curriculum-stage-main">' +
      '<div class="mx-curriculum-viewport" data-mx-curr-viewport>' +
      '<div class="mx-curriculum-canvas" data-mx-curr-canvas>' +
      '<img data-mx-curr-img alt="شريحة" draggable="false">' +
      '<div class="mx-curriculum-laser" data-mx-curr-laser hidden></div>' +
      '</div>' +
      '</div>' +
      hostChrome +
      '</div>' +
      '<div class="mx-curriculum-thumbs" data-mx-curr-thumbs></div>' +
      '</div>';

    var mount =
      $('meeting-stage') ||
      $('mx-video-stack') ||
      $('lk-stage');
    if (mount) {
      mount.insertBefore(el, mount.firstChild);
    } else {
      document.body.appendChild(el);
    }
    this.stage = el;
    this.img = el.querySelector('[data-mx-curr-img]');
    this.thumbs = el.querySelector('[data-mx-curr-thumbs]');
    this.counter = el.querySelector('[data-mx-curr-counter]');
    this.laserDot = el.querySelector('[data-mx-curr-laser]');
    this.titleEl = el.querySelector('[data-mx-curr-title]');
    this._wireStageControls();
  };

  Presenter.prototype._wireStageControls = function () {
    if (!this.isHost || !this.stage) return;
    var self = this;
    var bar = this.stage.querySelector('[data-mx-curr-host-bar]');
    if (!bar) return;
    bar.querySelector('[data-mx-curr-prev]')?.addEventListener('click', function () {
      self.setSlide(self.index - 1, true);
    });
    bar.querySelector('[data-mx-curr-next]')?.addEventListener('click', function () {
      self.setSlide(self.index + 1, true);
    });
    bar.querySelector('[data-mx-curr-zoom-in]')?.addEventListener('click', function () {
      self.setViewport(clamp(self.scale + 0.25, 1, 3.5), self.tx, self.ty, true);
    });
    bar.querySelector('[data-mx-curr-zoom-out]')?.addEventListener('click', function () {
      self.setViewport(clamp(self.scale - 0.25, 1, 3.5), self.tx, self.ty, true);
    });
    bar.querySelector('[data-mx-curr-zoom-reset]')?.addEventListener('click', function () {
      self.setViewport(1, 0, 0, true);
    });
    bar.querySelector('[data-mx-curr-laser]')?.addEventListener('click', function () {
      self.laserOn = !self.laserOn;
      bar.querySelector('[data-mx-curr-laser]').classList.toggle('is-active', self.laserOn);
      if (!self.laserOn) {
        self._showLaser(false, 0, 0);
        self._send('curriculum_pointer', { sessionId: self.sessionId, on: false, x: 0, y: 0 }, false);
      }
    });
    bar.querySelector('[data-mx-curr-fs]')?.addEventListener('click', function () {
      var target = self.stage;
      if (!document.fullscreenElement) {
        if (target.requestFullscreen) target.requestFullscreen().catch(function () {});
      } else if (document.exitFullscreen) {
        document.exitFullscreen().catch(function () {});
      }
    });
    bar.querySelector('[data-mx-curr-close]')?.addEventListener('click', function () {
      self.closeFromHost();
    });

    var viewport = this.stage.querySelector('[data-mx-curr-viewport]');
    if (viewport) {
      viewport.addEventListener('mousemove', function (ev) {
        if (!self.active || !self.laserOn) return;
        var rect = viewport.getBoundingClientRect();
        if (!rect.width || !rect.height) return;
        var x = clamp((ev.clientX - rect.left) / rect.width, 0, 1);
        var y = clamp((ev.clientY - rect.top) / rect.height, 0, 1);
        self._showLaser(true, x, y);
        var now = Date.now();
        if (now - self._pointerLast < 40) return;
        self._pointerLast = now;
        self._send(
          'curriculum_pointer',
          { sessionId: self.sessionId, on: true, x: x, y: y },
          false
        );
      });
      viewport.addEventListener('mouseleave', function () {
        if (!self.laserOn) return;
        self._showLaser(false, 0, 0);
        self._send('curriculum_pointer', { sessionId: self.sessionId, on: false, x: 0, y: 0 }, false);
      });
    }

    document.addEventListener('keydown', function (ev) {
      if (!self.active || !self.isHost) return;
      var tag = (ev.target && ev.target.tagName) || '';
      if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
      if (ev.key === 'ArrowLeft' || ev.key === 'PageUp') {
        ev.preventDefault();
        self.setSlide(self.index - 1, true);
      } else if (ev.key === 'ArrowRight' || ev.key === 'PageDown' || ev.key === ' ') {
        ev.preventDefault();
        self.setSlide(self.index + 1, true);
      } else if (ev.key === 'Escape') {
        self.closeFromHost();
      }
    });
  };

  Presenter.prototype._bindSync = function () {
    var self = this;
    if (this.api && typeof this.api.registerCurriculumHandler === 'function') {
      this.api.registerCurriculumHandler(function (msg, participant) {
        self._onData(msg, participant);
      });
    }

    /* Guests poll Cache-backed state over HTTP. */
    if (! this.isHost || this.config.pollAsHost) {
      this._startPoll();
    }
  };

  Presenter.prototype._startPoll = function () {
    var self = this;
    if (this._pollTimer || this._destroyed) return;
    var interval = Number(this.config.pollIntervalMs) || 1500;
    this._pollOnce();
    this._pollTimer = setInterval(function () {
      if (self._destroyed) return;
      self._pollOnce();
    }, interval);
  };

  Presenter.prototype._stopPoll = function () {
    if (this._pollTimer) {
      clearInterval(this._pollTimer);
      this._pollTimer = null;
    }
  };

  Presenter.prototype._pollOnce = function () {
    var self = this;
    if (this._pollInFlight || this._destroyed) return;
    this._pollInFlight = true;
    this._fetchGuestState()
      .then(function (state) {
        if (state) self._applyPolledState(state);
      })
      .finally(function () {
        self._pollInFlight = false;
      });
  };

  Presenter.prototype._applyPolledState = function (state) {
    if (!state || !state.active) return;
    var sid = String(state.session_id || '');
    var needOpen = !this.active || (sid && this.sessionId !== sid);
    if (needOpen) {
      this.sessionId = sid;
      this.title = state.title || '';
      this.slideCount = Number(state.slide_count || 0) || 0;
      this.index = Number(state.current_slide || 1) || 1;
      this.slides = (state.manifest && state.manifest.slides) || [];
      this._showStage();
      this._renderThumbs();
      this._paintSlide();
      return;
    }
    var slide = Number(state.current_slide || 0) || 0;
    if (slide >= 1 && slide !== this.index) {
      this.setSlide(slide, false);
    }
  };

  Presenter.prototype._send = function (type, payload, reliable) {
    if (!this.api || typeof this.api.sendCurriculum !== 'function') return;
    try {
      this.api.sendCurriculum(type, payload || {}, reliable !== false);
    } catch (e) {}
  };

  Presenter.prototype._onData = function (msg, participant) {
    if (!msg || !msg.t) return;
    var identity = participant && participant.identity ? participant.identity : '';
    var t = msg.t;
    var p = msg.p || {};

    if (t === 'curriculum_state_req') {
      if (this.isHost && this.active) {
        this._broadcastState();
      }
      return;
    }

    var controlTypes = {
      curriculum_open: 1,
      curriculum_state: 1,
      curriculum_slide: 1,
      curriculum_viewport: 1,
      curriculum_pointer: 1,
      curriculum_close: 1,
    };
    if (controlTypes[t]) {
      if (!isHostIdentity(identity)) {
        return;
      }
    }

    if (t === 'curriculum_close') {
      if (p.sessionId && this.sessionId && p.sessionId !== this.sessionId) return;
      this._hideStage();
      return;
    }

    if (t === 'curriculum_open' || (t === 'curriculum_state' && p.active !== false)) {
      this._applyRemoteOpen(p);
      return;
    }

    if (t === 'curriculum_state' && p.active === false) {
      this._hideStage();
      return;
    }

    if (!this.active) return;
    if (p.sessionId && this.sessionId && p.sessionId !== this.sessionId) return;

    if (t === 'curriculum_slide') {
      var idx = Number(p.index) || 1;
      this.setSlide(idx, false);
      return;
    }
    if (t === 'curriculum_viewport') {
      this.setViewport(Number(p.scale) || 1, Number(p.tx) || 0, Number(p.ty) || 0, false);
      return;
    }
    if (t === 'curriculum_pointer') {
      this._showLaser(!!p.on, Number(p.x) || 0, Number(p.y) || 0);
    }
  };

  Presenter.prototype._applyRemoteOpen = function (p) {
    if (!p || !p.sessionId) return;
    var self = this;
    this.sessionId = String(p.sessionId);
    this.title = String(p.title || '');
    this.slideCount = Number(p.pageCount || p.slide_count || 0) || 0;
    this.index = Number(p.index || p.current_slide || 1) || 1;

    if (this.isHost) {
      this._showStage();
      return;
    }

    this._fetchGuestState().then(function (state) {
      if (!state || !state.active) return;
      if (state.session_id !== self.sessionId && p.sessionId) {
        /* prefer DC session if fresher — still load HTTP if matches */
      }
      if (state.session_id) self.sessionId = state.session_id;
      self.title = state.title || self.title;
      self.slideCount = state.slide_count || self.slideCount;
      self.index = state.current_slide || self.index;
      self.slides = (state.manifest && state.manifest.slides) || [];
      self._showStage();
      self._renderThumbs();
      self._paintSlide();
      if (p.scale != null) {
        self.setViewport(Number(p.scale) || 1, Number(p.tx) || 0, Number(p.ty) || 0, false);
      }
    });
  };

  Presenter.prototype._broadcastState = function () {
    if (!this.active || !this.sessionId) return;
    this._send('curriculum_state', {
      active: true,
      sessionId: this.sessionId,
      title: this.title,
      pageCount: this.slideCount,
      index: this.index,
      scale: this.scale,
      tx: this.tx,
      ty: this.ty,
      itemId: this.config._itemId || null,
      materialId: this.config._materialId || null,
    });
  };

  Presenter.prototype.openPicker = function () {
    if (!this.isHost || !this.picker) return;
    this.picker.classList.add('is-open');
    this.picker.setAttribute('aria-hidden', 'false');
    this._loadCatalog();
  };

  Presenter.prototype.closePicker = function () {
    if (!this.picker) return;
    this.picker.classList.remove('is-open');
    this.picker.setAttribute('aria-hidden', 'true');
  };

  Presenter.prototype._loadCatalog = function () {
    var self = this;
    var list = $('mx-curriculum-picker-list');
    if (!list || !this.config.catalogUrl) return;
    list.innerHTML = '<div class="mx-curriculum-picker-empty">جاري التحميل…</div>';
    fetch(this.config.catalogUrl, {
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
      credentials: 'same-origin',
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        self._catalog = (data && data.items) || [];
        self._catalogLoaded = true;
        self._renderCatalog(self._catalog);
      })
      .catch(function () {
        list.innerHTML = '<div class="mx-curriculum-picker-empty">تعذر تحميل القائمة</div>';
      });
  };

  Presenter.prototype._filterCatalog = function (q) {
    q = String(q || '').trim().toLowerCase();
    var items = this._catalog || [];
    if (!q) {
      this._renderCatalog(items);
      return;
    }
    var filtered = items
      .map(function (group) {
        var mats = (group.materials || []).filter(function (m) {
          return (
            String(m.title || '').toLowerCase().indexOf(q) >= 0 ||
            String(group.item_title || '').toLowerCase().indexOf(q) >= 0
          );
        });
        return mats.length ? Object.assign({}, group, { materials: mats }) : null;
      })
      .filter(Boolean);
    this._renderCatalog(filtered);
  };

  Presenter.prototype._renderCatalog = function (items) {
    var list = $('mx-curriculum-picker-list');
    if (!list) return;
    list.innerHTML = '';
    if (!items || !items.length) {
      list.innerHTML = '<div class="mx-curriculum-picker-empty">لا توجد عروض جاهزة متاحة</div>';
      return;
    }
    var self = this;
    items.forEach(function (group) {
      var g = create('div', 'mx-curriculum-picker-group');
      g.appendChild(create('div', 'mx-curriculum-picker-group-title', escapeHtml(group.item_title || 'منهج')));
      (group.materials || []).forEach(function (m) {
        var row = create('button', 'mx-curriculum-picker-item');
        row.type = 'button';
        row.innerHTML =
          '<span class="mx-curriculum-picker-item-title"></span>' +
          '<span class="mx-curriculum-picker-item-meta"></span>';
        row.querySelector('.mx-curriculum-picker-item-title').textContent = m.title || 'عرض';
        row.querySelector('.mx-curriculum-picker-item-meta').textContent =
          (m.slide_count || 0) + ' شريحة · جاهز';
        row.addEventListener('click', function () {
          self.startPresent(group.item_id, m.id);
        });
        g.appendChild(row);
      });
      list.appendChild(g);
    });
  };

  Presenter.prototype.startPresent = function (itemId, materialId) {
    var self = this;
    if (!this.config.presentUrl) return;
    this.closePicker();

    var stopShare = Promise.resolve();
    if (this.api && typeof this.api.stopScreenShareIfActive === 'function') {
      stopShare = this.api.stopScreenShareIfActive();
    }

    stopShare
      .then(function () {
        return fetch(self.config.presentUrl, {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
          },
          credentials: 'same-origin',
          body: JSON.stringify({ item_id: itemId, material_id: materialId }),
        });
      })
      .then(function (r) {
        return r.json().then(function (data) {
          if (!r.ok || !data.ok) throw new Error((data && data.message) || 'تعذر بدء العرض');
          return data;
        });
      })
      .then(function (data) {
        self.sessionId = data.session_id;
        self.title = data.title || '';
        self.slideCount = data.slide_count || 0;
        self.index = data.current_slide || 1;
        self.slides = (data.manifest && data.manifest.slides) || [];
        self.config._itemId = data.item_id;
        self.config._materialId = data.material_id;
        self.scale = 1;
        self.tx = 0;
        self.ty = 0;
        self._showStage();
        self._renderThumbs();
        self._paintSlide();
        self._send('curriculum_open', {
          sessionId: self.sessionId,
          kind: 'material',
          itemId: data.item_id,
          materialId: data.material_id,
          title: self.title,
          pageCount: self.slideCount,
          index: self.index,
        });
        var btn = $('mx-ml-btn-curriculum');
        if (btn) {
          btn.classList.add('is-active');
          btn.setAttribute('aria-pressed', 'true');
        }
      })
      .catch(function (e) {
        if (self.api && typeof self.api.toast === 'function') {
          self.api.toast(e.message || 'تعذر بدء العرض');
        } else {
          alert(e.message || 'تعذر بدء العرض');
        }
      });
  };

  Presenter.prototype.setSlide = function (index, broadcast) {
    index = clamp(Math.round(Number(index) || 1), 1, Math.max(1, this.slideCount || 1));
    if (index === this.index && this.active) {
      this._paintSlide();
      return;
    }
    this.index = index;
    this._paintSlide();
    this._highlightThumb();
    if (broadcast && this.isHost) {
      this._send('curriculum_slide', { sessionId: this.sessionId, index: this.index });
      this._persistSlide();
    }
  };

  Presenter.prototype._persistSlide = function () {
    if (!this.config.slideUpdateUrl || !this.sessionId) return;
    fetch(this.config.slideUpdateUrl, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
      },
      credentials: 'same-origin',
      body: JSON.stringify({ session_id: this.sessionId, slide: this.index }),
    }).catch(function () {});
  };

  Presenter.prototype.setViewport = function (scale, tx, ty, broadcast) {
    this.scale = clamp(Number(scale) || 1, 1, 3.5);
    this.tx = Number(tx) || 0;
    this.ty = Number(ty) || 0;
    var canvas = this.stage && this.stage.querySelector('[data-mx-curr-canvas]');
    if (canvas) {
      canvas.style.transform =
        'translate(' + this.tx + 'px,' + this.ty + 'px) scale(' + this.scale + ')';
    }
    var resetBtn = this.stage && this.stage.querySelector('[data-mx-curr-zoom-reset]');
    if (resetBtn) resetBtn.textContent = Math.round(this.scale * 100) + '%';
    if (broadcast && this.isHost) {
      var self = this;
      clearTimeout(this._viewportTimer);
      this._viewportTimer = setTimeout(function () {
        self._send('curriculum_viewport', {
          sessionId: self.sessionId,
          scale: self.scale,
          tx: self.tx,
          ty: self.ty,
        });
      }, 120);
    }
  };

  Presenter.prototype._paintSlide = function () {
    if (!this.img) return;
    var slide = null;
    for (var i = 0; i < this.slides.length; i++) {
      if ((Number(this.slides[i].index) || 0) === this.index) {
        slide = this.slides[i];
        break;
      }
    }
    var url = slide && slide.image_url ? slide.image_url : null;
    if (url && !this.isHost && this.config.guestToken) {
      url = appendToken(url, this.config.guestToken);
    }
    if (url) {
      this.img.src = url;
    }
    if (this.counter) {
      this.counter.textContent = this.index + ' / ' + (this.slideCount || 1);
    }
    if (this.titleEl) this.titleEl.textContent = this.title || '';
  };

  Presenter.prototype._renderThumbs = function () {
    if (!this.thumbs) return;
    this.thumbs.innerHTML = '';
    if (!this.isHost) {
      this.thumbs.style.display = 'none';
      return;
    }
    this.thumbs.style.display = '';
    var self = this;
    this.slides.forEach(function (s) {
      var btn = create('button', 'mx-curriculum-thumb');
      btn.type = 'button';
      btn.dataset.index = String(s.index);
      var img = create('img');
      img.alt = '';
      img.loading = 'lazy';
      var turl = s.thumb_url || s.image_url;
      if (turl && !self.isHost && self.config.guestToken) {
        turl = appendToken(turl, self.config.guestToken);
      }
      if (turl) img.src = turl;
      btn.appendChild(img);
      btn.addEventListener('click', function () {
        self.setSlide(Number(s.index), true);
      });
      self.thumbs.appendChild(btn);
    });
    this._highlightThumb();
  };

  Presenter.prototype._highlightThumb = function () {
    if (!this.thumbs) return;
    var nodes = this.thumbs.querySelectorAll('.mx-curriculum-thumb');
    for (var i = 0; i < nodes.length; i++) {
      nodes[i].classList.toggle('is-active', Number(nodes[i].dataset.index) === this.index);
    }
  };

  Presenter.prototype._showLaser = function (on, x, y) {
    if (!this.laserDot) return;
    if (!on) {
      this.laserDot.hidden = true;
      return;
    }
    this.laserDot.hidden = false;
    this.laserDot.style.left = clamp(x, 0, 1) * 100 + '%';
    this.laserDot.style.top = clamp(y, 0, 1) * 100 + '%';
  };

  Presenter.prototype._showStage = function () {
    this.active = true;
    if (this.stage) {
      this.stage.classList.add('is-open');
      this.stage.setAttribute('aria-hidden', 'false');
    }
    var host = $('meeting-stage') || $('mx-video-stack') || $('lk-stage');
    if (host) host.classList.add('has-curriculum');
    document.body.classList.add('mx-curriculum-on');
    if (this.api && typeof this.api.setCurriculumActive === 'function') {
      this.api.setCurriculumActive(true);
    }
  };

  Presenter.prototype._hideStage = function () {
    this.active = false;
    this.sessionId = null;
    this.slides = [];
    this.laserOn = false;
    if (this.stage) {
      this.stage.classList.remove('is-open');
      this.stage.setAttribute('aria-hidden', 'true');
    }
    var host = $('meeting-stage') || $('mx-video-stack') || $('lk-stage');
    if (host) host.classList.remove('has-curriculum');
    document.body.classList.remove('mx-curriculum-on');
    if (document.fullscreenElement && this.stage && document.fullscreenElement === this.stage) {
      try {
        document.exitFullscreen();
      } catch (e) {}
    }
    var btn = $('mx-ml-btn-curriculum');
    if (btn) {
      btn.classList.remove('is-active');
      btn.setAttribute('aria-pressed', 'false');
    }
    if (this.api && typeof this.api.setCurriculumActive === 'function') {
      this.api.setCurriculumActive(false);
    }
  };

  Presenter.prototype.closeFromHost = function () {
    if (!this.isHost) return;
    var sid = this.sessionId;
    this._send('curriculum_close', { sessionId: sid });
    this._hideStage();
    if (this.config.stopUrl) {
      fetch(this.config.stopUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf(),
        },
        credentials: 'same-origin',
        body: JSON.stringify({}),
      }).catch(function () {});
    }
  };

  Presenter.prototype._fetchGuestState = function () {
    var self = this;
    if (!this.config.stateUrl) return Promise.resolve(null);
    var url = this.config.stateUrl;
    if (this.config.guestToken) {
      url = appendToken(url, this.config.guestToken);
    }
    if (this.sessionId) {
      url += (url.indexOf('?') >= 0 ? '&' : '?') + 'session_id=' + encodeURIComponent(this.sessionId);
    }
    return fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (!data || !data.ok || !data.active) {
          if (self.active) self._hideStage();
          return null;
        }
        return data;
      })
      .catch(function () {
        return null;
      });
  };

  Presenter.prototype.destroy = function () {
    if (this._destroyed) return;
    this._destroyed = true;
    this._stopPoll();
    if (this.isHost && this.active) {
      this.closeFromHost();
    } else {
      this._hideStage();
    }
    this.closePicker();
  };

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function attach(lkApi, config) {
    var p = new Presenter(lkApi, config || {});
    if (lkApi) {
      lkApi.openCurriculumPresenter = function () {
        if (p.isHost) p.openPicker();
      };
      lkApi.closeCurriculumPresenter = function () {
        if (p.isHost) p.closeFromHost();
        else p._hideStage();
      };
    }
    return p;
  }

  global.MxClassroomCurriculumPresenter = { attach: attach };
})(typeof window !== 'undefined' ? window : globalThis);
