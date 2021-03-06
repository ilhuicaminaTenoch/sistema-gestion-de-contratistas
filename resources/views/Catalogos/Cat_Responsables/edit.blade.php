@extends('layouts.admin')
@section('contenido')
<div class="row">
	<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
		<h3>Editar Responsable: {{$Responsable->res}}</h3>
		@if (count($errors)>0)
		<div class="alert alert-danger">
			<ul>
				@foreach($errors->all() as $error)
					<li>{{$error}}</li>
				@endforeach
			</ul>
		</div>
		@endif
		
		{!!Form::model($Responsable,['method'=>'PATCH', 'route'=>['Cat_Responsables.update',$Responsable->id_responsable]])!!}
			{{Form::token()}}
			<div class="form-group">
				<label for="responsable">Responsable</label>
				<input type="text" name="responsable" class="form-control" value="{{$Responsable->responsable}}" placeholder="Nombre del Responsable...">
			</div>
			<div class="form-group">
				<button class="btn btn-primary" type="submit">Guardar</button>
				<a href="/Catalogos/Cat_Responsables" class="btn btn-danger">Cancelar</a>
			</div> 	
		{!!Form::close()!!}
	</div>
</div>
@endsection