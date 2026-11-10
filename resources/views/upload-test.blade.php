<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('web.upload_test.title') }}</title>
    @vite('resources/css/app.css')
</head>

<body class="min-h-screen bg-[#f8fafb] text-[#30373d]">
    <main class="mx-auto flex w-full max-w-2xl flex-col gap-6 px-6 py-16">

        <h1 class="text-2xl font-bold">{{ __('web.upload_test.heading') }}</h1>

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('upload_path'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ __('web.upload_test.uploaded_to', ['path' => session('upload_path')]) }}
            </div>
        @endif

        @if (session('upload_url'))
            <div class="rounded-2xl border border-[#ebebeb] bg-white p-6">
                <p class="mb-3 text-sm text-[#5b6770]">
                    {{ __('web.upload_test.preview_note') }}
                </p>

                <img src="{{ session('upload_url') }}" alt="{{ __('web.upload_test.image_alt') }}"
                    class="w-full rounded-xl border border-[#ebebeb] object-contain">
            </div>
        @endif

        <form method="POST" action="{{ route('upload-test.store') }}" enctype="multipart/form-data"
            class="flex flex-col gap-4 rounded-2xl border border-[#ebebeb] bg-white p-6">
            @csrf

            <input type="file" name="file"
                class="w-full rounded-lg border border-[#ebebeb] bg-white px-4 py-3 text-sm" required>

            <button type="submit"
                class="inline-flex w-fit items-center justify-center rounded-full bg-[#99745c] px-6 py-2 text-sm font-semibold text-white">
                {{ __('web.upload_test.upload') }}
            </button>
        </form>

    </main>
</body>

</html>
