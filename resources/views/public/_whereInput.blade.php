{{-- "Where" box: type any city, a district or a property name; suggestions list places with properties. $id, $value, $destinations --}}
<input type="text" name="where" id="{{ $id }}" class="form-control" list="{{ $id }}-list" value="{{ $value }}"
       placeholder="City, district or hotel name" maxlength="100" autocomplete="off">
<datalist id="{{ $id }}-list">
    @foreach($destinations as $destination)
        <option value="{{ $destination['value'] }}">{{ $destination['label'] }}</option>
    @endforeach
</datalist>
