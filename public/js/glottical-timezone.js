/**
 * حصتك timezone helpers — aligned with «برج التنسيق الزمني».
 * Storage is always UTC; display uses IANA zones + slot quality for the viewer.
 */
(function (global) {
  'use strict';

  var CAIRO_TZ = 'Africa/Cairo';

  var US_ZONES = [
    { key: 'ET', tz: 'America/New_York', label: 'شرقي — نيويورك' },
    { key: 'CT', tz: 'America/Chicago', label: 'وسطي — شيكاغو' },
    { key: 'MT', tz: 'America/Denver', label: 'جبلي — دنفر' },
    { key: 'PT', tz: 'America/Los_Angeles', label: 'الهادي — لوس أنجلوس' }
  ];

  function pad(n) {
    return String(n).padStart(2, '0');
  }

  function qualityForUsHour(h) {
    h = ((h % 24) + 24) % 24;
    if (h >= 7 && h < 20) return 'good';
    if ((h >= 6 && h < 7) || (h >= 20 && h < 22)) return 'caution';
    return 'poor';
  }

  function fmtHour24(date, timeZone) {
    try {
      var s = new Intl.DateTimeFormat('en-US', {
        timeZone: timeZone,
        hour: '2-digit',
        hour12: false,
        hourCycle: 'h23'
      }).format(date);
      return parseInt(s, 10) % 24;
    } catch (e) {
      return date.getUTCHours();
    }
  }

  function fmtTime(date, timeZone, locale) {
    try {
      return new Intl.DateTimeFormat(locale || 'ar-EG-u-nu-latn', {
        timeZone: timeZone,
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      }).format(date);
    } catch (e) {
      return '--:--';
    }
  }

  function qualityForInstant(date, timeZone) {
    return qualityForUsHour(fmtHour24(date, timeZone || CAIRO_TZ));
  }

  function detectTimezone() {
    try {
      return (Intl.DateTimeFormat().resolvedOptions().timeZone || '').trim() || CAIRO_TZ;
    } catch (e) {
      return CAIRO_TZ;
    }
  }

  global.حصتكTimezone = {
    CAIRO_TZ: CAIRO_TZ,
    US_ZONES: US_ZONES,
    pad: pad,
    qualityForUsHour: qualityForUsHour,
    qualityForInstant: qualityForInstant,
    fmtHour24: fmtHour24,
    fmtTime: fmtTime,
    detectTimezone: detectTimezone
  };
})(typeof window !== 'undefined' ? window : this);
