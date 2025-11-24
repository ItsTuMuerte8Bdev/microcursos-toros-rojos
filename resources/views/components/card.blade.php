@props(["classes" => 'card', "style" => '', "bodyClasses" => 'card-body', "bodyStyle" => ''])
<div class="{{ $classes }}" style="{{ $style }}">
    <div class="{{ $bodyClasses }}" style="{{ $bodyStyle }}">
        {{ $slot }}
    </div>
</div>
