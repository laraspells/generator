@php
$n = 4;
$menuItems = config('{? config_key ?}.menu');
$dropdown = count($menuItems) > $n ? array_splice($menuItems, $n, count($menuItems) - $n) : [];
@endphp
<ul class="nav navbar-nav navbar-right">
  @foreach($menuItems as $menu)
  <li class="{{ Request::route()->getName() == $menu['route']? 'active' : '' }}">
    <a href="{{ route($menu['route']) }}">
      <i class="fa {{ $menu['icon'] }}"></i>
      {{ $menu['label'] }}
    </a>
  </li>
  @endforeach
  @if($dropdown)
  <li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
      <i class="fa fa-bars"></i>
    </a>
    <ul class="dropdown-menu">
      @foreach($dropdown as $menu)
      <li class="{{ Request::route()->getName() == $menu['route']? 'active' : '' }}">
        <a href="{{ route($menu['route']) }}">
          <i class="fa {{ $menu['icon'] }}"></i>
          {{ $menu['label'] }}
        </a>
      </li>
      @endforeach
    </ul>
  </li>
  @endif
</ul>
