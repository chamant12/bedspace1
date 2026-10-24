@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">My Properties</h4>
                    <div class="table-responsive">
                      <table class="table table-striped">
                        <thead>
                          <tr>
                            <th> Property </th>
                            <th> City </th>
                            <th> District </th>
                          </tr>
                        </thead>
                        <tbody>
                            @foreach($properties as $property)
                          <tr>
                            <td class="py-1">
                              {{$property->property_name}}
                              @unless($property->is_active)<span class="badge badge-secondary">Inactive</span>@endunless
                            </td>
                            <td> {{$property->city->city_name}} </td>
                            <td>
                                {{$property->city->district->district_name}}
                            </td>
                            <td>
                                <a href="/properties/{{$property->id}}/details" data-toggle="tooltip" title="Property details" class="btn btn-primary">
                                    <i class="fa fa-eye"></i>
                                </a>
                                @if(auth()->user()->canEditProperties())
                                <a href="/view-property/{{$property->id}}" data-toggle="tooltip" title="Edit property" class="btn btn-outline-primary">
                                    <i class="ti-pencil"></i>
                                </a>
                                <a href="/add-base-rate/{{$property->id}}" data-toggle="tooltip" title="Rates calendar" style="text-decoration:none;" class="btn btn-success">
                                <i class="ti-tag"></i>
                                </a>
                                <a href="/inventory/{{$property->id}}" data-toggle="tooltip" title="Inventory" style="text-decoration:none;" class="btn btn-info">
                                <i class="ti-layout-grid2"></i>
                                </a>
                                <a href="/rooms/{{$property->id}}" data-toggle="tooltip" title="Room numbers" style="text-decoration:none;" class="btn btn-secondary">
                                <i class="ti-key"></i>
                                @endif
                                @if(auth()->user()->isOwner())
                                </a>
                                <form method="POST" action="/property/{{$property->id}}/toggle-active" style="display:inline;"
                                      onsubmit="return confirm('{{ $property->is_active ? 'Deactivate this property? Guests will no longer see it.' : 'Activate this property?' }}');">
                                    @csrf
                                    <button type="submit" class="btn {{ $property->is_active ? 'btn-danger' : 'btn-success' }}" title="{{ $property->is_active ? 'Deactivate property' : 'Activate property' }}">
                                        <i class="{{ $property->is_active ? 'ti-close' : 'ti-check' }}"></i>
                                    </button>
                                </form>
                                @endif
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