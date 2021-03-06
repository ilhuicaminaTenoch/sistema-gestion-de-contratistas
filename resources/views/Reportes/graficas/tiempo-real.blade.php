@extends('layouts.admin')
@section('contenido')
<div class="row">
	<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
		<h3>Reportes</h3>
		@if (count($errors)>0)
			<div class="alert alert-danger">
				<ul>
					@foreach($errors->all() as $error)
						<li>{{$error}}</li>
					@endforeach
				</ul>
			</div>
		@endif
	</div>
</div>
<div class="row">
	<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
		{!!Form::open(array('url'=> '/graficas/preview-tiempo-real','method'=>'GET','autocomplete'=>'off'))!!}
			<label>Seleccionar fechas:</label>
			<div class="form-group">
				<div class="input-group input-daterange">
					<input type="date" class="form-control" value="" name="fechaInicial" id="fechaInicial">
				</div>
			</div>
			<div class="form-group">
				<button class="btn btn-primary" type="submit">Generar</button>
			</div>
		{!!Form::close()!!}
	</div>
</div>
<div class="row">
	<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">

	</div>
</div>

@endsection
