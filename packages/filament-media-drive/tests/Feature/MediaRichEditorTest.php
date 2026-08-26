<?php

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Mamenein\FilamentMediaDrive\Forms\Components\MediaMarkdownEditor;
use Mamenein\FilamentMediaDrive\Forms\Components\MediaRichEditor;

test('media markdown editor is a markdown editor', function () {
    expect(MediaMarkdownEditor::make('body'))->toBeInstanceOf(MarkdownEditor::class);
});

test('media rich editor is a rich editor with the same ingest hooks as markdown', function () {
    $field = MediaRichEditor::make('body');

    expect($field)->toBeInstanceOf(RichEditor::class)
        ->and($field->getFileAttachmentsDiskName())->toBe('s3')
        ->and(MediaMarkdownEditor::make('body')->getFileAttachmentsDiskName())->toBe('s3');
});
