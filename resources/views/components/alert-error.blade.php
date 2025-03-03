@props(['message'])

@if ($message)
    <div {{ $attributes->merge(['class' => 'mb-4 p-3 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-md']) }}>
        {{ $message }}
    </div>
@endif