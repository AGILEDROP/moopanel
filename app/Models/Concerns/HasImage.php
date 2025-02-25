<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

trait HasImage
{
    protected static function bootHasImage(): void
    {
        self::deleting(function ($model) {
            if (isset($model->img_path) && Storage::disk($model->imageDisk())->exists($model->img_path)) {
                Storage::disk($model->imageDisk())->delete($model->img_path);
            }
        });
    }

    protected function getArrayableItems(array $values): array
    {
        if (! in_array('image', $this->appends)) {
            $this->appends[] = 'image';
        }

        return parent::getArrayableItems($values);
    }

    public function image(): Attribute
    {
        return new Attribute(get: fn () => $this->getImageUrlAttribute());
    }

    public function getImageUrlAttribute(): string
    {
        return ($this->{$this->getDefaultImagePathAttribute()} && Storage::disk($this->imageDisk())->exists($this->{$this->getDefaultImagePathAttribute()}))
            ? Storage::disk($this->imageDisk())->url($this->{$this->getDefaultImagePathAttribute()})
            : $this->defaultImageUrl();
    }

    protected function defaultImageUrl(): string
    {
        $name = trim(collect(explode(' ', $this->{$this->getDefaultImageNameAttribute()}))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));

        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color='.$this->defaultImageTextColor().'&background='.$this->defaultImageBackgroundColor();
    }

    public function getDefaultImagePathAttribute(): string
    {
        return 'img_path';
    }

    public function getDefaultImageNameAttribute(): string
    {
        return 'name';
    }

    public function defaultImageBackgroundColor(): string
    {
        return 'E12A26';
    }

    public function defaultImageTextColor(): string
    {
        return 'FFFFFF';
    }

    public function imageDisk(): string
    {
        return config('file-uploads.disk');
    }
}
