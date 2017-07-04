@php
$id = "input-{$name}";
$label = isset($label)? $label : ucwords(snake_case(camel_case($name), ' '));
$required = isset($required)? (bool) $required : false;
$empty_option = isset($empty_option)? $empty_option : 'Pick '.$label;
$value = isset($value)? $value : [];
@endphp

<div class="form-group {{ $errors->has($name)? 'has-error' : '' }}">
  <label for="{{ $id }}">
    {{ $label }}
    @if($required)
    <strong class="text-danger">*</strong>
    @endif
  </label>
  @foreach($options as $option['value'] => $option['label'])
    <div class="input-checkbox">
      <input id="cb-{{ $id }}-{{ $option['value'] }}" name="{{ $name }}[]" type='checkbox' value="{{ $option['value'] }}" {{ in_array($option['value'], $value)? 'checked' : '' }}>
      <span>{{ $option['label'] }}</span>
    </div>
  @endforeach
  @if($errors->has($name))
  <div class="help-block">{{ $errors->first($name) }}</div>
  @endif
  @if(isset($help))
  <div class="help-block">{{ $help }}</div>
  @endif
</div>
