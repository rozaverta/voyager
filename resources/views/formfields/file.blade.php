@php
    /** @var \TCG\Voyager\Models\DataRow $row */
    /** @var \TCG\Voyager\Models\DataType $dataType */
    /** @var \Illuminate\Database\Eloquent\Model $dataTypeContent */

    $dataRowSlug = null;
    $isRemove = isset($dataTypeContent->{$row->field});
    if($isRemove && $dataType->name !== $dataTypeContent->getTable()) {
        $dataRowComponent = \TCG\Voyager\Models\DataType::where("name", $dataTypeContent->getTable())->first();
        if($dataRowComponent) {
            $dataRowSlug = $dataRowComponent->slug;
        }
    }
@endphp
@if($isRemove)
    @if(json_decode($dataTypeContent->{$row->field}) !== null)
        @foreach(json_decode($dataTypeContent->{$row->field}) as $file)
          <div data-field-name="{{ $row->field }}">
            <a class="fileType" target="_blank"
              href="{{ Storage::disk(config('voyager.storage.disk'))->url($file->download_link) ?: '' }}"
              data-file-name="{{ $file->original_name }}" data-id="{{ $dataTypeContent->getKey() }}"
              @if($dataRowSlug) data-slug="{{ $dataRowSlug }}" @endif
            >
              {{ $file->original_name ?: '' }}
            </a>
            <a href="#" class="voyager-x remove-multi-file"></a>
          </div>
        @endforeach
    @else
      <div data-field-name="{{ $row->field }}">
        <a class="fileType" target="_blank"
          href="{{ Storage::disk(config('voyager.storage.disk'))->url($dataTypeContent->{$row->field}) }}"
          data-file-name="{{ $dataTypeContent->{$row->field} }}" data-id="{{ $dataTypeContent->getKey() }}"
          @if($dataRowSlug) data-slug="{{ $dataRowSlug }}" @endif
        >>
          Download
        </a>
        <a href="#" class="voyager-x remove-single-file"></a>
      </div>
    @endif
@endif
<input @if($row->required == 1 && !isset($dataTypeContent->{$row->field})) required @endif type="file" name="{{ $row->field }}[]" multiple="multiple">
