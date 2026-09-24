<?php

namespace App\Services;

use App\Models\HiringForm;
use App\Models\HiringFormField;
use App\Models\InstructorProfile;
use App\Models\TutorApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class HiringFormService
{
    public static function ensureDefaultForm(): HiringForm
    {
        $form = HiringForm::query()->orderBy('id')->first();
        if ($form) {
            return $form;
        }

        $form = HiringForm::create([
            'title' => 'طلب التوظيف كمعلّم',
            'description' => 'أكمل بياناتك الشخصية والمستندات لإرسال طلبك للمراجعة.',
            'is_published' => true,
            'settings' => ['require_intro_video' => true],
        ]);

        $defs = [
            ['type' => HiringFormField::TYPE_SECTION, 'label' => 'البيانات الشخصية', 'required' => false],
            ['type' => HiringFormField::TYPE_SHORT_TEXT, 'label' => 'الاسم الكامل', 'required' => true, 'key' => 'full_name'],
            ['type' => HiringFormField::TYPE_PHONE, 'label' => 'الجوال / واتساب', 'required' => true, 'key' => 'phone'],
            ['type' => HiringFormField::TYPE_SELECT, 'label' => 'النوع', 'required' => false, 'key' => 'gender', 'options' => ['male' => 'ذكر', 'female' => 'أنثى']],
            ['type' => HiringFormField::TYPE_SHORT_TEXT, 'label' => 'الجنسية', 'required' => false, 'key' => 'nationality'],
            ['type' => HiringFormField::TYPE_SHORT_TEXT, 'label' => 'الدولة / المدينة', 'required' => false, 'key' => 'city'],
            ['type' => HiringFormField::TYPE_SECTION, 'label' => 'العنوان والسيرة', 'required' => false],
            ['type' => HiringFormField::TYPE_SHORT_TEXT, 'label' => 'عنوان مختصر', 'required' => true, 'key' => 'headline'],
            ['type' => HiringFormField::TYPE_LONG_TEXT, 'label' => 'نبذة / سيرة ذاتية', 'required' => true, 'key' => 'bio'],
            ['type' => HiringFormField::TYPE_LONG_TEXT, 'label' => 'الخبرات', 'required' => true, 'key' => 'experience'],
            ['type' => HiringFormField::TYPE_SHORT_TEXT, 'label' => 'المؤهل', 'required' => false, 'key' => 'education'],
            ['type' => HiringFormField::TYPE_NUMBER, 'label' => 'سنوات الخبرة', 'required' => false, 'key' => 'years_experience'],
            ['type' => HiringFormField::TYPE_SECTION, 'label' => 'المستندات والفيديو', 'required' => false],
            ['type' => HiringFormField::TYPE_FILE, 'label' => 'الصورة الشخصية', 'required' => true, 'key' => 'photo', 'settings' => ['file_kind' => 'image']],
            ['type' => HiringFormField::TYPE_FILE, 'label' => 'البطاقة / جواز السفر', 'required' => true, 'key' => 'id_document', 'settings' => ['file_kind' => 'image_pdf']],
            ['type' => HiringFormField::TYPE_FILE, 'label' => 'الشهادة / الإجازة', 'required' => true, 'key' => 'certificate', 'settings' => ['file_kind' => 'image_pdf']],
            ['type' => HiringFormField::TYPE_FILE, 'label' => 'فيديو تعريفي (ملف)', 'required' => false, 'key' => 'intro_video', 'settings' => ['file_kind' => 'video']],
            ['type' => HiringFormField::TYPE_URL, 'label' => 'رابط فيديو تعريفي', 'required' => false, 'key' => 'intro_video_url'],
        ];

        foreach ($defs as $i => $def) {
            $options = null;
            if (! empty($def['options'])) {
                $options = [];
                foreach ($def['options'] as $value => $label) {
                    $options[] = ['value' => (string) $value, 'label' => (string) $label];
                }
            }

            HiringFormField::create([
                'hiring_form_id' => $form->id,
                'type' => $def['type'],
                'label' => $def['label'],
                'is_required' => (bool) ($def['required'] ?? false),
                'options' => $options,
                'system_key' => $def['key'] ?? null,
                'sort_order' => ($i + 1) * 10,
                'is_active' => true,
                'settings' => $def['settings'] ?? null,
            ]);
        }

        return $form->fresh(['fields']);
    }

    public static function publishedForm(): HiringForm
    {
        $form = HiringForm::published();
        if (! $form) {
            $form = self::ensureDefaultForm();
            $form->update(['is_published' => true]);
        }

        return $form->load(['activeFields']);
    }

    /**
     * @return array{answers: array<int, mixed>, mapped: array<string, mixed>}
     */
    public static function processSubmission(HiringForm $form, Request $request, TutorApplication $application, User $user): array
    {
        $fields = $form->activeFields;
        $rules = [];
        $messages = [];
        $existingAnswers = is_array($application->answers) ? $application->answers : [];

        foreach ($fields as $field) {
            if ($field->isSection()) {
                continue;
            }

            $key = 'answers.'.$field->id;
            $fileKey = 'hiring_upload.'.$field->id;
            $required = $field->is_required;

            if ($field->isFile()) {
                $hasExisting = filled($existingAnswers[(string) $field->id]['path'] ?? null)
                    || filled(self::existingMappedPath($application, $field->system_key));
                $rules[$fileKey] = [($required && ! $hasExisting) ? 'required' : 'nullable', 'file'];
                $kind = $field->settings['file_kind'] ?? 'any';
                $rules[$fileKey] = array_merge($rules[$fileKey], self::fileRules($kind));
                if ($required && ! $hasExisting) {
                    $messages[$fileKey.'.required'] = 'حقل «'.$field->label.'» مطلوب.';
                }
                continue;
            }

            $base = [($required ? 'required' : 'nullable')];
            $rules[$key] = match ($field->type) {
                HiringFormField::TYPE_EMAIL => array_merge($base, ['email', 'max:190']),
                HiringFormField::TYPE_PHONE => array_merge($base, ['string', 'max:40']),
                HiringFormField::TYPE_NUMBER => array_merge($base, ['numeric']),
                HiringFormField::TYPE_DATE => array_merge($base, ['date']),
                HiringFormField::TYPE_URL => array_merge($base, ['url', 'max:500']),
                HiringFormField::TYPE_LONG_TEXT => array_merge($base, ['string', 'max:20000']),
                HiringFormField::TYPE_CHECKBOX => array_merge($base, ['array']),
                HiringFormField::TYPE_SELECT, HiringFormField::TYPE_RADIO => array_merge($base, ['string', 'max:255']),
                default => array_merge($base, ['string', 'max:2000']),
            };

            if ($required) {
                $messages[$key.'.required'] = 'حقل «'.$field->label.'» مطلوب.';
            }
        }

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $answers = $existingAnswers;
        $mapped = [];

        foreach ($fields as $field) {
            if ($field->isSection()) {
                continue;
            }

            if ($field->isFile()) {
                /** @var UploadedFile|null $upload */
                $upload = $request->file('hiring_upload.'.$field->id);
                if ($upload instanceof UploadedFile) {
                    $path = self::storeUpload($upload, $field, $existingAnswers[(string) $field->id]['path'] ?? null);
                    $answers[(string) $field->id] = [
                        'type' => 'file',
                        'label' => $field->label,
                        'path' => $path,
                        'name' => $upload->getClientOriginalName(),
                        'system_key' => $field->system_key,
                    ];
                    if ($field->system_key) {
                        $mapped[$field->system_key] = $path;
                    }
                } elseif (isset($answers[(string) $field->id])) {
                    $answers[(string) $field->id]['label'] = $field->label;
                    if ($field->system_key && ! empty($answers[(string) $field->id]['path'])) {
                        $mapped[$field->system_key] = $answers[(string) $field->id]['path'];
                    }
                }
                continue;
            }

            $value = $request->input('answers.'.$field->id);
            if ($field->acceptsMultiple()) {
                $value = array_values(array_filter((array) $value, fn ($v) => $v !== null && $v !== ''));
            } elseif (is_string($value)) {
                $value = trim($value);
            }

            $answers[(string) $field->id] = [
                'type' => $field->type,
                'label' => $field->label,
                'value' => $value,
                'system_key' => $field->system_key,
            ];

            if ($field->system_key && $value !== null && $value !== '' && $value !== []) {
                $mapped[$field->system_key] = is_array($value) ? implode(', ', $value) : $value;
            }
        }

        $settings = $form->settings ?? [];
        if (! empty($settings['require_intro_video'])) {
            $hasVideo = filled($mapped['intro_video'] ?? null)
                || filled($mapped['intro_video_url'] ?? null)
                || filled($application->intro_video_path)
                || filled($application->intro_video_url);
            if (! $hasVideo) {
                throw ValidationException::withMessages([
                    'intro_video' => 'أرفق فيديو تعريفي أو ضع رابط الفيديو.',
                ]);
            }
        }

        if (! empty($mapped['phone'])) {
            $phone = preg_replace('/\s+/', '', (string) $mapped['phone']) ?: (string) $mapped['phone'];
            $mapped['phone'] = $phone;
            $exists = User::query()
                ->where('phone', $phone)
                ->where('id', '!=', $user->id)
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'phone' => 'رقم الجوال مسجّل مسبقاً.',
                ]);
            }
        }

        return ['answers' => $answers, 'mapped' => $mapped];
    }

    public static function applyMappedToApplication(TutorApplication $application, array $mapped, array $answers, HiringForm $form): void
    {
        $payload = [
            'hiring_form_id' => $form->id,
            'answers' => $answers,
            'status' => TutorApplication::STATUS_PENDING,
            'admin_notes' => null,
        ];

        foreach ([
            'full_name', 'phone', 'nationality', 'city', 'gender',
            'headline', 'bio', 'experience', 'education',
        ] as $col) {
            if (array_key_exists($col, $mapped) && filled($mapped[$col])) {
                $payload[$col] = $mapped[$col];
            }
        }

        if (array_key_exists('years_experience', $mapped) && $mapped['years_experience'] !== '' && $mapped['years_experience'] !== null) {
            $payload['years_experience'] = (int) $mapped['years_experience'];
        }

        if (! empty($mapped['photo'])) {
            $payload['photo_path'] = $mapped['photo'];
        }
        if (! empty($mapped['id_document'])) {
            $payload['id_document_path'] = $mapped['id_document'];
        }
        if (! empty($mapped['certificate'])) {
            $payload['certificate_path'] = $mapped['certificate'];
        }
        if (! empty($mapped['intro_video'])) {
            $payload['intro_video_path'] = $mapped['intro_video'];
        }
        if (array_key_exists('intro_video_url', $mapped)) {
            $payload['intro_video_url'] = filled($mapped['intro_video_url']) ? trim((string) $mapped['intro_video_url']) : null;
        }

        $application->update($payload);
    }

    public static function syncUserAndProfile(User $user, TutorApplication $application, array $mapped): void
    {
        $introVideoUrl = filled($application->intro_video_url)
            ? $application->intro_video_url
            : TutorApplicationStorage::publicUrl($application->intro_video_path);

        $user->forceFill([
            'name' => $mapped['full_name'] ?? $user->name,
            'phone' => $mapped['phone'] ?? $user->phone,
            'gender' => $mapped['gender'] ?? $user->gender,
            'bio' => $mapped['bio'] ?? $user->bio,
            'portfolio_intro_video_url' => $introVideoUrl ?: $user->portfolio_intro_video_url,
            'profile_image' => $application->photo_path ?: $user->profile_image,
        ])->save();

        $profilePayload = [
            'headline' => $mapped['headline'] ?? $application->headline,
            'bio' => $mapped['bio'] ?? $application->bio,
            'experience' => $mapped['experience'] ?? $application->experience,
            'photo_path' => $application->photo_path,
            'status' => InstructorProfile::STATUS_PENDING_REVIEW,
            'submitted_at' => now(),
            'rejection_reason' => null,
        ];

        if (! empty($application->teaching_subject_ids)) {
            $profilePayload['teaching_subject_ids'] = $application->teaching_subject_ids;
        }
        if (! empty($application->curriculum_types)) {
            $profilePayload['curriculum_types'] = $application->curriculum_types;
        }

        InstructorProfile::updateOrCreate(
            ['user_id' => $user->id],
            $profilePayload
        );
    }

    private static function fileRules(string $kind): array
    {
        $maxKb = (int) config('upload_limits.max_upload_kb', 40960);
        $videoMaxKb = min(max($maxKb, 40960), 102400);

        return match ($kind) {
            'image' => ['image', 'max:'.$maxKb],
            'pdf' => ['mimes:pdf', 'max:'.$maxKb],
            'video' => ['mimetypes:video/mp4,video/webm,video/quicktime', 'max:'.$videoMaxKb],
            'image_pdf' => ['mimes:jpg,jpeg,png,webp,pdf', 'max:'.$maxKb],
            default => ['mimes:jpg,jpeg,png,webp,pdf,mp4,webm,mov,doc,docx', 'max:'.$videoMaxKb],
        };
    }

    private static function storeUpload(UploadedFile $file, HiringFormField $field, ?string $oldPath): string
    {
        $key = $field->system_key;
        $kind = $field->settings['file_kind'] ?? 'any';

        return match ($key) {
            'photo' => TutorApplicationStorage::storePhoto($file, $oldPath),
            'id_document' => TutorApplicationStorage::storeIdDocument($file, $oldPath),
            'certificate' => TutorApplicationStorage::storeCertificate($file, $oldPath),
            'intro_video' => TutorApplicationStorage::storeVideo($file, $oldPath),
            default => match ($kind) {
                'video' => TutorApplicationStorage::storeVideo($file, $oldPath),
                'image' => TutorApplicationStorage::storePhoto($file, $oldPath),
                default => TutorApplicationStorage::storeIdDocument($file, $oldPath),
            },
        };
    }

    private static function existingMappedPath(TutorApplication $application, ?string $systemKey): ?string
    {
        return match ($systemKey) {
            'photo' => $application->photo_path,
            'id_document' => $application->id_document_path,
            'certificate' => $application->certificate_path,
            'intro_video' => $application->intro_video_path,
            default => null,
        };
    }
}
