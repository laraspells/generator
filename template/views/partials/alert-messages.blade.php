@foreach(['info', 'warning', 'danger', 'success'] as $type)
  @if(session($type))
  <div class="alert alert-message alert-{{ $type }}">
    <b class="close" data-dismiss="alert">&times;</b>
    <p>{{ session($type) }}</p>
  </div>
  @endif
@endforeach