{{-- "Waiting for you" strip on every dashboard. Not limited to the period: an item waits until decided. --}}
@use('App\Support\Approvals\ApprovalInbox')
@if(($approvalSummary ?? null) && $approvalSummary['can'])
  @php($apItems = array_values(array_filter($approvalSummary['items'], fn ($i) => $i['count'] > 0)))
  <div class="panel h-auto mb-3">
    <div class="panel-h">
      <h6>Waiting for your approval</h6>
      <a class="hint" href="{{ route('approvals.index') }}">
        @if($approvalSummary['total']) {{ $approvalSummary['total'] }} in all · open the list → @else open the list → @endif
      </a>
    </div>
    <div class="panel-b">
      @if($apItems)
        <div class="os-tiles">
          @foreach($apItems as $item)
            @php($days = ApprovalInbox::ageDays($item['oldest']))
            <a class="os-tile" href="{{ $item['url'] }}">
              <span class="lbl">{{ $item['label'] }}</span>
              <span class="val">{{ number_format($item['count']) }}</span>
              <span class="note">{{ $item['stage'] }}</span>
              @if($days !== null)
                <span class="chip {{ ApprovalInbox::ageTone($days) }}">oldest {{ ApprovalInbox::ageLabel($days) }}</span>
              @endif
            </a>
          @endforeach
        </div>
      @else
        <div class="empty">Nothing is waiting for you.</div>
      @endif
    </div>
  </div>
@endif
