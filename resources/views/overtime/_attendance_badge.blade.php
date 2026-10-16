{{-- What this overtime entry put on the attendance sheet (OvertimeAttendanceSync). --}}
@foreach($row->attendanceLinks as $link)
    @php
        $who = $link->role === 'cover' ? 'O.T' : 'Absent';
        [$css, $text] = match (true) {
            $link->state === 'conflict' => ['bg-label-danger', $who . ': not on sheet'],
            $link->state === 'skipped' => ['bg-label-secondary', $who . ': no client'],
            $link->state === 'matched' => ['bg-label-success', $who . ': already present'],
            $row->isFullyApproved() => ['bg-label-success', $who . ': on sheet'],
            default => ['bg-label-warning', $who . ': sheet pending'],
        };
    @endphp
    <span class="badge {{ $css }} d-block mt-1 text-start" style="font-size:.68rem;white-space:nowrap;width:max-content" title="{{ $link->message }}">{{ $text }}</span>
@endforeach
