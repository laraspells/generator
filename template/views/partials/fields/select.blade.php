@php
$id = "input-{$name}";
$label = isset($label)? $label : ucwords(snake_case(camel_case($name), ' '));
$required = isset($required)? (bool) $required : false;
$empty_option = isset($empty_option)? $empty_option : 'Pick '.$label;
$value = isset($value)? $value : '';
@endphp

<div class="form-group {{ $errors->has($name)? 'has-error' : '' }}">
  <label for="{{ $id }}">
    {{ $label }}
    @if($required)
    <strong class="text-danger">*</strong>
    @endif
  </label>
  <select
    class="form-control"
    name="{{ $name }}" 
    id="{{ $id }}"
    {{ $required? 'required' : '' }}>
    @if($empty_option)
    <option value="">{{ $empty_option }}</option>  
    @endif
    @foreach($options as $option)
    <option value="{{ $option['value'] }}" {{ $value == $option['value']? 'selected' : '' }}>{{ $option['label'] }}</option>
    @endforeach
  </select>
  @if($errors->has($name))
  <div class="help-block">{{ $errors->first($name) }}</div>
  @endif
  @if(isset($help))
  <div class="help-block">{{ $help }}</div>
  @endif
</div>
