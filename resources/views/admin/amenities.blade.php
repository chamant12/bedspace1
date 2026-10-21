@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Add Amenity</h4>
            <form method="POST" action="/admin/amenities" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label>Amenity name<span style="color:red;">*</span></label>
                    <input type="text" name="amenity" class="form-control" value="{{ old('amenity') }}" required maxlength="255">
                </div>
                <div class="col-md-3">
                    <label>Applies to<span style="color:red;">*</span></label>
                    <select name="amenity_type" class="form-control" required>
                        <option value="property">Property</option>
                        <option value="roomType">Room Type</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Amenities</h4>
            <p class="card-description">Owners choose from this list. Rename an amenity to fix its label everywhere it is used. Deactivated amenities can no longer be added by owners.</p>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Amenity</th>
                            <th>Applies to</th>
                            <th>Used by</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($amenities as $amenity)
                        <tr>
                            <td>
                                <form method="POST" action="/admin/amenities/{{ $amenity->id }}" class="d-flex gap-2" id="amenity-form-{{ $amenity->id }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="amenity_type" value="{{ $amenity->amenity_type }}">
                                    <input type="text" name="amenity" class="form-control" value="{{ $amenity->amenity }}" required maxlength="255">
                                </form>
                            </td>
                            <td>{{ $amenity->amenity_type === 'roomType' ? 'Room Type' : 'Property' }} @unless($amenity->is_active)<span class="badge badge-secondary">Inactive</span>@endunless</td>
                            <td>{{ $usage[$amenity->id] ?? 0 }}</td>
                            <td class="d-flex gap-2">
                                <button type="submit" form="amenity-form-{{ $amenity->id }}" class="btn btn-sm btn-primary">Save</button>
                                <form method="POST" action="/admin/amenities/{{ $amenity->id }}/toggle-active"
                                      onsubmit="return confirm('{{ $amenity->is_active ? 'Deactivate this amenity? Owners will no longer be able to add it.' : 'Activate this amenity?' }}');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $amenity->is_active ? 'btn-danger' : 'btn-success' }}">
                                        {{ $amenity->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
