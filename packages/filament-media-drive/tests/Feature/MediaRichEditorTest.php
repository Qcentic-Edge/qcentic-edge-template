<?php

use App\Models\User;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mamenein\FilamentMediaDrive\Forms\Components\MediaMarkdownEditor;
use Mamenein\FilamentMediaDrive\Forms\Components\MediaRichEditor;
use Mamenein\FilamentMediaDrive\Support\EditorImageStore;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('s3');
    Queue::fake();
});

test('media markdown editor is a markdown editor', function () {
    expect(MediaMarkdownEditor::make('body'))->toBeInstanceOf(MarkdownEditor::class);
});

test('media rich editor wires the same store and url hooks as markdown', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $rich = MediaRichEditor::make('body');
    $markdown = MediaMarkdownEditor::make('body');

    $hook = fn (object $field, string $property): mixed => (new \ReflectionProperty($field, $property))->getValue($field);

    expect($rich)->toBeInstanceOf(RichEditor::class)
        ->and($rich->getFileAttachmentsDiskName())->toBe('s3')
        ->and($markdown->getFileAttachmentsDiskName())->toBe('s3')
        ->and($hook($rich, 'saveUploadedFileAttachmentUsing'))->toBeInstanceOf(\Closure::class)
        ->and($hook($rich, 'getFileAttachmentUrlUsing'))->toBeInstanceOf(\Closure::class)
        ->and($hook($markdown, 'saveUploadedFileAttachmentUsing'))->toBeInstanceOf(\Closure::class)
        ->and($hook($markdown, 'getFileAttachmentUrlUsing'))->toBeInstanceOf(\Closure::class);

    $media = EditorImageStore::store(UploadedFile::fake()->create('hook.png', 40, 'image/png'));

    expect($rich->getFileAttachmentUrl($media))->toBe(EditorImageStore::url($media))
        ->and($markdown->getFileAttachmentUrl($media))->toBe(EditorImageStore::url($media))
        ->and($rich->getFileAttachmentUrl('https://files.example-cdn.test/x.png'))->toBe('https://files.example-cdn.test/x.png');
});
