@extends('layouts.panel')

@section('content')
<div class="row">
	<div class="col-md-12 mb-4">
		<div class="card">
			<div class="card-header">Dashboard</div>

			<div class="card-body ">
				<p>Seleccione una opción del Panel Izquierdo o de las opciones
				debajo</p>
				@if (auth()->user()->role == 'administrador')
				<div class="row form-group text-center">
					<div class="col-lg-2 col-sm-12 col-md-2">
						<a href="/orders/create" class="btn btn-warning">Generar Orden</a>
					</div>

					<div class="col-lg-2 col-sm-12 col-md-2">
						<a href="/nurses" class="btn btn-info">Listado Enfermeria</a>
					</div>

					<div class="col-lg-2 col-sm-12 col-md-2">
						<a href="/medicals" class="btn btn-success">Listado Medicina</a>
					</div>
				</div>

				@elseif (auth()->user()->role == 'enfermera')
				<div class="row form-group">
					<div class="col-lg-2 col-sm-12 col-md-2">
						<a href="/orders/create" class="btn btn-warning">Generar Orden</a>
					</div>

					<div class="col-lg-2 col-sm-12 col-md-2">
						<a href="/nurses" class="btn btn-info">Listado Enfermeria</a>
					</div>
				</div>
				@elseif (auth()->user()->role == 'doctor')
				<div class="row form-group bg-">
					<div class="col-lg-2 col-sm-12 col-md-2">
						<a href="/orders/create" class="btn btn-warning">Generar Orden</a>
					</div>

					<div class="col-lg-2 col-sm-12 col-md-2">
						<a href="/medicals" class="btn btn-info">Listado Medicina</a>
					</div>
				</div>
				@endif
				<br>

				@if (auth()->user()->role == 'administrador')
				<div class="row form-group">
					<div class="col-lg-4">
						<div style="width: 18rem;">
							<div class="card card-stats mb-4 mb-lg-0 bg-black">
								<div class="card-body">
									<div class="row">
										<div class="col">
											<h5 class="card-title text-uppercase text-muted mb-0">SALA AMARILLA</h5>
											<span class="h2 font-weight-bold mb-0">{{ $room_yellow }}</span>
											<p>Pacientes</p>
										</div>
										<div class="col-auto">
											<div class="icon icon-shape bg-yellow text-white rounded-circle shadow">
												<i class="fas fa-door-open"></i>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-4">
						<div style="width: 18rem;">
							<div class="card card-stats mb-4 mb-lg-0">
								<div class="card-body">
									<div class="row">
										<div class="col">
											<h5 class="card-title text-uppercase text-muted mb-0">SALA VERDE</h5>
											<span class="h2 font-weight-bold mb-0">{{ $room_green }}</span>
											<p>Pacientes</p>
										</div>
										<div class="col-auto">
											<div class="icon icon-shape bg-success text-white rounded-circle shadow">
												<i class="fas fa-door-open"></i>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-4">
						<div style="width: 18rem;">
							<div class="card card-stats mb-4 mb-lg-0">
								<div class="card-body">
									<div class="row">
										<div class="col">
											<h5 class="card-title text-uppercase text-muted mb-0">SALA AZUL</h5>
											<span class="h2 font-weight-bold mb-0">{{ $room_blue }}</span>
											<p>Pacientes</p>
										</div>
										<div class="col-auto">
											<div class="icon icon-shape bg-default text-white rounded-circle shadow">
												<i class="fas fa-door-open"></i>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="row form-group">
					<div class="col-lg-4 col-m-4">
						<div style="width: 18rem;">
							<div class="card card-stats mb-4 mb-lg-0 bg-black">
								<div class="card-body">
									<div class="row">
										<div class="col">
											<h5 class="card-title text-uppercase text-muted mb-0">USUARIOS</h5>
											<span class="h2 font-weight-bold mb-0">{{ $total_users }}</span>
											<p>Registrado(s)</p>
										</div>
										<div class="col-auto">
											<div class="icon icon-shape bg-danger text-white rounded-circle shadow">
												<i class="fas fa-users"></i>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-4 col-m-4">
						<div style="width: 18rem;">
							<div class="card card-stats mb-4 mb-lg-0 bg-black">
								<div class="card-body">
									<div class="row">
										<div class="col">
											<h5 class="card-title text-uppercase text-muted mb-0">PACIENTES</h5>
											<span class="h2 font-weight-bold mb-0">{{ $total_patients }}</span>
											<p>Registrado(s)</p>
										</div>
										<div class="col-auto">
											<div class="icon icon-shape bg-warning text-white rounded-circle shadow">
												<i class="fas fa-procedures"></i>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-3 col-m-3">
						<div style="width: 18rem;">
							<div class="card card-stats mb-4 mb-lg-0 bg-black">
								<div class="card-body">
									<div class="row">
										<div class="col">
											<h5 class="card-title text-uppercase text-muted mb-0">ORDENES</h5>
											<span class="h2 font-weight-bold mb-0">{{ $total_orders }}</span>
											<p>Resgistradas</p>
										</div>
										<div class="col-auto">
											<div class="icon icon-shape bg-info text-white rounded-circle shadow">
												<i class="fas fa-folder-open"></i>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				@endif
			</div>
		</div>
	</div>
	@endsection
