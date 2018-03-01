<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
  <meta name="description" content="">
  <meta name="author" content="">

  <title>{{ $title or '{? schema.name ?}' }}</title>

  <!-- Bootstrap core CSS -->
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

  <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
  <link href="{{ asset('admin-template/css/ie10-viewport-bug-workaround.css') }}" rel="stylesheet">

  <!-- Custom styles for this template -->
  {{--  <link href="{{ asset('css/app.css') }}" rel="stylesheet">  --}}
  <link href="{{ asset('admin-template/css/style.css') }}" rel="stylesheet">

  <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
  <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body>
  <nav class="navbar navbar-default navbar-static-top">
    <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="#">{? schema.name ?}</a>
    </div>
    <div id="navbar" class="collapse navbar-collapse">
      @include('{? view_namespace ?}layout.navbar-menu')
    </div><!--/.nav-collapse -->
    </div>
  </nav>

  <div class="container">
    @yield('content')
  </div><!-- /.container -->

  <!-- Bootstrap core JavaScript
  ================================================== -->
  <!-- Placed at the end of the document so the pages load faster -->
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
  <!-- Bootstrap Core JavaScript -->
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
  <script src="{{ asset('admin-template/js/ie10-viewport-bug-workaround.js') }}"></script>
</body>
</html>
