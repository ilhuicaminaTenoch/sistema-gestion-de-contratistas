@extends('layouts.admin')
@section('contenido')
<div class="row">
	<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
		<h3>Nuevo Puesto:</h3>
		@if (count($errors)>0)
		<div class="alert alert-danger">
			<ul>
				@foreach($errors->all() as $error)
				<li>{{$error}}</li>
				@endforeach
			</ul>
		</div>
		@endif

		{!!Form::open(array('url'=>'Catalogos\Cat_Puestos','method'=>'POST','autocomplete'=>'off'))!!}
			{{Form::token()}}
			<div class="form-group">
				<label for="puesto">Puesto</label>
				<input type="text" name="puesto" class="form-control" placeholder="Nombre del Puesto...">
				
			</div>
			<div class="form-group">
				<button class="btn btn-primary" type="submit">Guardar</button>
				<a href="/Catalogos/Cat_Puestos" class="btn btn-danger">Cancelar</a>
			</div>
		{!!Form::close()!!}
	</div>
</div>
@endsection