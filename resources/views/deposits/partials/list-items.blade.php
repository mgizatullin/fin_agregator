@foreach ($items as $item)
    <x-deposit-card :item="$item" />
@endforeach
