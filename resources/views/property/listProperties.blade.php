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
                            </td>
                            <td> {{$property->city->city_name}} </td>
                            <td>
                                {{$property->city->district->district_name}}
                            </td>
                            <td>
                                <a href="/view-property/{{$property->id}}" data-toggle="tooltip" title="View Property Details" class="btn btn-primary">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="/add-base-rate/{{$property->id}}" data-toggle="tooltip" title="Add base rate" style="text-decoration:none;" class="btn btn-success">
                                <i class="ti-tag"></i>
                                </a>
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