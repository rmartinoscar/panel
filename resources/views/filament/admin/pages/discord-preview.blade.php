<div class="container">
    <div class="bg-gray-800 text-white p-4 rounded-lg">
        {{-- Message Content --}}
        @foreach($this->messages as $message)
            <p class="mb-2">{{ nl2br(e($message['content'])) }}</p>
        @endforeach

        {{-- Embeds --}}
        @foreach($this->embeds as $embed)
            <div class="p-3 mt-3 rounded-lg" style="border-left: 5px solid {{ $embed['color'] }}; background-color: #23272A;">
                @if(!empty($embed['title']))
                    <h3 class="font-bold">{{ e($embed['title']) }}</h3>
                @endif
                @if(!empty($embed['description']))
                    <p>{{ nl2br(e($embed['description'])) }}</p>
                @endif
            </div>
        @endforeach
    </div>
</div>