@extends('layouts.admin')
@section('contenido')

<div class="row">
	<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
		<h3>Compañias <a href="/Catalogos/Cat_Empresas/create"><button class="btn btn-success">Nuevo</button></a></h3>
		@include('Catalogos.Cat_Empresas.search')
	</div>
</div>
<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<div class="table-responsive">
			<table class="table table-striped table-bordered table condensed table-hover">
				<thead>
					<th>Id</th>
					<th>Compañia</th>
					<th>Giro</th>
					<th>Responsable</th>
					<th>Proyecto</th>
					<th>Área</th>
					<th>Activo</th>
					<th>Opciones</th>
				</thead>
				@foreach ($Compania as $emp)
				<tr>
					<td>{{$emp->id_compania}}</td>
					<td>{{$emp->compania}}</td>
					<td>{{$emp->giro}}</td>
					<td>{{$emp->responsable}}</td>
					<td>{{$emp->proyecto}}</td>
					<td>{{$emp->area}}</td>
					
					@if ($emp->activo==1)
						<td>Activo</td>
					@else
						<td>Inactivo</td>
					@endif
					<td>

						<a href="{{URL::action('EmpresaController@edit',$emp->id_compania)}}"><button class="btn btn-info">Editar</button></a>
						<a href="" data-target="#modal-delete-{{$emp->id_compania}}" data-toggle="modal">
						<button class="btn btn-danger">Eliminar</button></a>
					</td>
				</tr>
				@include('Catalogos.Cat_Empresas.modal')
				@endforeach
			</table>
		</div>
		{{$Compania->appends(Request::only(['searchText']))->render()}}
	</div>
</div>

@endsection