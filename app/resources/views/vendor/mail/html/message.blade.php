<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
<br><span style="font-size: 13px; font-weight: normal; color: #64748b;">{{ config('portal.orgao') }}</span>
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
{{ config('app.name') }} · {{ config('portal.orgao') }}<br>
Mensagem automática, não responda este e-mail.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
