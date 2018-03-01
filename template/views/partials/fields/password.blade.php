@php
$id = "input-{$name}";
$label = isset($label)? $label : ucwords(snake_case(camel_case($name), ' '));
$required = isset($required)? (bool) $required : false;
@endphp

<div class="form-group {{ $errors->has($name)? 'has-error' : '' }}">
  <label for="{{ $id }}">
    {{ $label }}
    @if($required)
    <strong class="text-danger">*</strong>
    @endif
  </label>
  <input
    type="password"
    class="form-control"
    value="{{ $value or '' }}"
    name="{{ $name }}"
    id="{{ $id }}"
    {{ $required? 'required' : '' }}
  />
  @if($errors->has($name))
  <div class="help-block">{{ $errors->first($name) }}</div>
  @endif
  @if(isset($help))
  <div class="help-block">{{ $help }}</div>
  @endif
</div>
