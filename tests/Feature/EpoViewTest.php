<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class EpoViewTest extends TestCase
{
    public function test_layout_renders_page_script_stacks(): void
    {
        $html = Blade::render(<<<'BLADE'
@extends('layouts.app')

@section('content')
    <main>Konten E-PO</main>
@endsection

@push('scripts')
    <script id="epo-test-script">window.epoTest = true;</script>
@endpush
BLADE);

        $this->assertStringContainsString('id="epo-test-script"', $html);
        $this->assertStringContainsString('window.epoTest = true;', $html);
    }
}
