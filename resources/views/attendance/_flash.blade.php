@foreach(['success' => 'success', 'info' => 'info', 'warning' => 'warning', 'error' => 'danger'] as $key => $color)
    @if(session($key))
        <div class="alert alert-{{ $color }} alert-dismissible" role="alert">{{ session($key) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    @endif
@endforeach
@if($errors->any())
    <div class="alert alert-danger" role="alert">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif
