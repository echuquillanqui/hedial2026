@foreach ($nurse_list as $nurse)

<p>{{ $nurse->id }}.- {{ $nurse->patient }} - {{ $nurse->created_at }} / {{ $nurse->room }} - {{ $nurse->shift }}</p>

@endforeach