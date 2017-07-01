<ul class="nav navbar-nav">
  @foreach(config('{? config_key ?}.menu') as $menu)
  <li class="{{ Request::route()->getName() == $menu['route']? 'active' : '' }}">
    <a href="{{ route($menu['route']) }}">
      <i class="fa {{ $menu['icon'] }}"></i>
      {{ $menu['label'] }}
    </a>
  </li>
  @endforeach
</ul>