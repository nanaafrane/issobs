{{-- One row of the pending employees list. Needs $employee and $key. --}}
                            <tr>
                                <td> <input class="checkBoxes form-check-input" type="checkbox" name="employees[]" value="{{ $employee->id }}" /></td>
                                <td> {{ $key + 1 }} </td>

                                @php $move = ($pendingMoves ?? collect())->get($employee->id); @endphp
                                <td>
                                    <span class="badge {{ $employee->status == 'Active' ? 'bg-label-success' : 'bg-label-danger' }}">{{$employee->status}}</span>
                                    @if($move && $move->type !== 'hire')
                                        {{-- What head office is approving: the request's date and reason. --}}
                                        <div class="small text-muted">
                                            {{ \App\Support\EmployeeMovements::LABELS[$move->type] }}{{ $move->effective_date ? ' from ' . $move->effective_date->format('d M Y') : '' }}{{ $move->reason ? ' · ' . \App\Support\EmployeeMovements::reasonLabel($move->reason) : '' }}
                                        </div>
                                    @endif
                                </td>

                                    <td> 
                                        @if($employee->assit_status == 'approved')
                                        {{$employee->user?->name}}
                                        {{ $employee->assit_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-success">{{$employee->assit_status}}</span>
                                        @else
                                        {{$employee->user?->name}}
                                        {{ $employee->assit_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-danger">{{$employee->assit_status}}</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($employee->bran_status == 'approved')
                                        {{$employee->user1?->name}}
                                        {{ $employee->bran_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-success">{{$employee->bran_status}}</span>
                                        @else
                                        {{$employee->user1?->name}}
                                        {{ $employee->bran_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-danger">{{$employee->bran_status}}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{$employee->user2?->name}}
                                        <span class="badge bg-label-danger">{{$employee->ho_status}}</span>
                                    </td>


                                <td> FWSS {{ $employee->id }}  </td>
                                <td>{{$employee->name}}  </td>
                                <td>{{ $employee->gender }}  </td>
                                <td>{{ $employee->phone_number }}  </td>
                                <td>{{ $employee->date_of_joining?->format('l F d, Y') }} </td> 
                                <td> {{ $employee->department?->name }} </td>
                                <td> {{ $employee->role?->name }}  </td>
                                <td> {{ $employee->field?->name }}   </td>
                                <td>{{ $employee->client?->name }} {{ $employee->client?->business_name }} </td>
                                <td> {{ $employee->location }} </td>
                                <td> {{ $employee->payment_type }}  </td>
                                <td> {{  $employee->paymentInfo?->bank?->name  }} </td>
                                <td> {{  $employee->paymentInfo?->acc_number  }} </td>

                                <!-- <td> {{ $employee->status_date?->format('F, Y') }} </td> -->
                                 @if($employee->tax_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->tax_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                <td> {{  $employee->paymentInfo?->tin_number  }} </td>

                                @if($employee->ssnit_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->ssnit_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                 <td> {{  $employee->paymentInfo?->ssnit_number  }} </td>
                                <td> {{$employee->basic_salary}} </td>
                                <td> {{$employee->allowances}} </td>
                                <td> {{ $employee->created_at?->format('F, Y') }} </td>
                                <!-- <td> {{ $employee->created_at?->diffForHumans() }} </td>
                                <td>{{ $employee->updated_at?->format('F, Y') }} </td>
                                <td>{{ $employee->updated_at?->diffForHumans() }} </td> -->
                                <!-- <td>{{  $employee->user?->name }}</td> -->
                               <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}"><i class="icon-base bx bxs-bullseye"></i> view</a>
                                      @if($employee->status == 'Pending')
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>
                                      @endif
                                        @if(Auth::user()->hasNotRole(['Manager']))
                                        <hr>
                                        <a class="dropdown-item" href="{{url('employeesSalary', $employee->id)}}"><i class="icon-base bx bx-money-withdraw"></i> Salaries</a>
                                       @endif
                                    </div>
                                </div>
                            </td>
                            </tr>
