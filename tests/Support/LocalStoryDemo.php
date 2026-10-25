<?php

namespace Tests\Support;

use App\Models\Story;
use App\Models\StoryModerationEvent;
use App\Support\StoryDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LocalStoryDemo
{
    public static function seed(): void
    {
        if (! StoryDatabase::isLocalPreview()) {
            throw new RuntimeException('Demo fixtures are restricted to the isolated local preview.');
        }
        $categories = Story::CATEGORIES;
        $texts = [
            'Fiksi demo: hari ini aku berhasil menyelesaikan tugas kecil yang lama tertunda. Rasanya lega sekali.',
            'Fiksi demo: kami sepakat menjaga jarak dengan baik. Sedih masih ada, tetapi berjalan sore membantuku menerima perubahan perlahan.',
            'Fiksi demo: seorang teman mengirim pesan sederhana untuk menanyakan kabarku. Percakapan singkat itu membuat hari yang panjang terasa lebih ringan.',
            'Fiksi demo: percobaan resep pertamaku akhirnya berhasil. Kami tertawa di dapur sambil berbagi hasilnya bersama keluarga.',
            'Fiksi demo: hujan turun ketika aku menunggu bus. Aku menikmati suara air dan melanjutkan bacaan yang belum selesai.',
        ];
        for ($number = 1; $number <= 15; $number++) {
            $visibility = in_array($number, [10, 11, 15], true) ? 'private' : 'public';
            $status = match ($number) {
                9, 10, 15 => 'pending', 12 => 'rejected', 13 => 'hidden', default => 'approved'
            };
            $body = $number === 8 ? mb_substr(str_repeat('Fiksi demo panjang: aku belajar merayakan kemajuan kecil dan menghargai jeda. ', 25), 0, 1490) : $texts[($number - 1) % 5];
            $story = Story::withTrashed()->firstOrCreate(['idempotency_key' => 'd3a00000-0000-4000-8000-'.str_pad((string) $number, 12, '0', STR_PAD_LEFT)], [
                'category' => $categories[($number - 1) % 5], 'body' => $body,
                'visibility' => $visibility, 'status' => $status, 'publication_consent' => $visibility === 'public',
                'social_consent' => false, 'policy_version' => 'LOCAL-DEMO-NOT-APPROVED', 'consented_at' => now(),
                'published_at' => $visibility === 'public' && $status === 'approved' ? now() : null,
                'request_hash' => hash('sha256', 'demo-request-'.$number), 'fingerprint' => hash('sha256', 'demo-actor-'.$number),
                'duplicate_hash' => hash('sha256', $body), 'supports_count' => 0,
            ]);
            if (! $story->wasRecentlyCreated) {
                continue;
            }
            if ($status !== 'pending') {
                StoryModerationEvent::create(['story_id' => $story->id, 'action' => 'demo_fixture',
                    'from_status' => 'pending', 'to_status' => $status, 'reason' => 'Data fiksi lokal untuk QA.']);
            }
            if ($number === 1) {
                foreach ([1, 2] as $reaction) {
                    DB::connection('stories')->table('story_reactions')->insert(['story_id' => $story->id,
                        'fingerprint' => hash('sha256', 'demo-reaction-'.$reaction), 'created_at' => now()]);
                }
                $story->update(['supports_count' => 2]);
                DB::connection('stories')->table('story_reports')->insert(['story_id' => $story->id,
                    'fingerprint' => hash('sha256', 'demo-report'), 'reason' => 'other', 'details' => 'Laporan fiksi untuk menguji antrean.',
                    'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
