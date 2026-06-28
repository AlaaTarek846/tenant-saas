<?php

namespace App\Traits;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HasMediaTrait
{
    /* =========================
     | العلاقات
     ========================= */
    public function medias()
    {
        return $this->morphMany(Media::class, 'model');
    }

    /* =========================
     | جلب صورة واحدة
     ========================= */
    public function getSingleMedia(string $collection)
    {
        return $this->medias()
            ->where('collection_name', $collection)
            ->first();
    }


    /* =========================
 | جلب صورة واحدة
 ========================= */
    public function getMultipleMedia(string $collection)
    {
        return $this->medias()
            ->where('collection_name', $collection)
            ->orderBy('order_column', 'asc')
            ->get();
    }
    /* =========================
     | إضافة / تحديث صورة واحدة
     | - لو مفيش ملف → لا شيء
     | - لو فيه جديد → يحذف القديم + يضيف الجديد
     ========================= */
    public function setSingleMedia(
        string $collection,
        UploadedFile $file,
        string $path = 'uploads',
        string $disk = 'public'
    ) {
        $oldMedia = $this->getSingleMedia($collection);
        $storedPath = $file->store($path, $disk);

        if ($oldMedia) {
            $this->deleteMediaFile($oldMedia);
            $oldMedia->delete();
        }

        return $this->medias()->create([
            'collection_name' => $collection,
            'name'            => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'file_name'       => $storedPath,
            'mime_type'       => $file->getClientMimeType(),
            'disk'            => $disk,
            'size'            => $file->getSize(),
            'manipulations'   => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);
    }

    /* =========================
     | إضافة صور متعددة مع ترتيب
     | كل عنصر: ['file' => UploadedFile, 'name' => 'xxx', 'order' => int]
     ========================= */
    public function addMultipleMedia(
        string $collection,
        array $files,
        string $path = 'uploads',
        string $disk = 'public'
    ) {
        $order = $this->medias()
                ->where('collection_name', $collection)
                ->max('order_column') ?? 0;

        foreach ($files as $fileData) {
            if (!isset($fileData['file']) || !$fileData['file'] instanceof UploadedFile) {
                continue;
            }

            $file = $fileData['file'];
            $storedPath = $file->store($path, $disk);

            $this->medias()->create([
                'collection_name' => $collection,
                'name'            => $fileData['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'file_name'       => $storedPath,
                'mime_type'       => $file->getClientMimeType(),
                'disk'            => $disk,
                'size'            => $file->getSize(),
                'order_column'    => $fileData['order'] ?? (++$order),
                'manipulations'   => [],
                'custom_properties' => [],
                'generated_conversions' => [],
                'responsive_images' => [],
            ]);
        }
    }

    /* =========================
     | حذف صورة واحدة
     ========================= */
    public function deleteSingleMedia(string $collection)
    {
        $media = $this->getSingleMedia($collection);

        if (!$media) return false;

        $this->deleteMediaFile($media);
        return $media->delete();
    }

    /* =========================
     | حذف كل الصور (DB + Storage)
     ========================= */
    public function cleanMedia()
    {
        foreach ($this->medias as $media) {
            $this->deleteMediaFile($media);
            $media->delete();
        }
    }

    /* =========================
     | Helper: حذف الملف من storage
     ========================= */
    protected function deleteMediaFile(Media $media): void
    {
        if ($media->disk && $media->file_name && Storage::disk($media->disk)->exists($media->file_name)) {
            Storage::disk($media->disk)->delete($media->file_name);
        }
    }
    /* =========================
     | مزامنة صور متعددة مع ترتيب وحذف القديم غير المرسل
     | كل عنصر إما:
     | - صورة جديدة: ['file' => UploadedFile, 'name' => 'xxx', 'order' => int]
     | - أو صورة قديمة: ['id' => int, 'order' => int]
     ========================= */
    public function syncMultipleMedia(
        string $collection,
        array $items,
        string $path = 'uploads',
        string $disk = 'public'
    ) {
        $existingMedia = $this->medias()
            ->where('collection_name', $collection)
            ->get();

        $providedIds = collect($items)
            ->filter(fn($item) => isset($item['id']))
            ->pluck('id')
            ->map(fn($id) => (int)$id)
            ->toArray();

        // حذف الصور التي لم تعد موجودة في الـ payload
        foreach ($existingMedia as $media) {
            if (!in_array($media->id, $providedIds)) {
                $this->deleteMediaFile($media);
                $media->delete();
            }
        }

        // إضافة الجديد وتحديث ترتيب القديم
        foreach ($items as $itemData) {
            if (isset($itemData['id'])) {
                // صورة قديمة -> نُحدث الترتيب فقط
                $media = $existingMedia->firstWhere('id', $itemData['id']);
                if ($media) {
                    $media->update(['order_column' => $itemData['order'] ?? $media->order_column]);
                }
            } elseif (isset($itemData['file']) && $itemData['file'] instanceof UploadedFile) {
                // صورة جديدة -> نرفعها
                $file = $itemData['file'];
                $storedPath = $file->store($path, $disk);

                $this->medias()->create([
                    'collection_name' => $collection,
                    'name'            => $itemData['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_name'       => $storedPath,
                    'mime_type'       => $file->getClientMimeType(),
                    'disk'            => $disk,
                    'size'            => $file->getSize(),
                    'order_column'    => $itemData['order'] ?? 0,
                    'manipulations'   => [],
                    'custom_properties' => [],
                    'generated_conversions' => [],
                    'responsive_images' => [],
                ]);
            }
        }
    }
}
