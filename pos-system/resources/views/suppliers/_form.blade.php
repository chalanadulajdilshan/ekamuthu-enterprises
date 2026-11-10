<div class="modal-bg" id="{{ $id }}">
    <div class="modal">
        <form method="post" action="{{ $action }}">
            @csrf @if ($verb === 'PUT') @method('PUT') @endif
            <div class="modal-head"><h3>{{ $title }}</h3><button type="button" class="x" data-close>&times;</button></div>
            <div class="modal-body">
                <div class="field"><label class="lbl">Company name *</label><input class="input" name="name" required maxlength="120" value="{{ $s->name ?? '' }}"></div>
                <div class="grid-2">
                    <div class="field"><label class="lbl">Contact person</label><input class="input" name="contact_person" maxlength="120" value="{{ $s->contact_person ?? '' }}"></div>
                    <div class="field"><label class="lbl">Phone</label><input class="input" name="phone" maxlength="30" value="{{ $s->phone ?? '' }}"></div>
                </div>
                <div class="field"><label class="lbl">Email</label><input class="input" type="email" name="email" maxlength="120" value="{{ $s->email ?? '' }}"></div>
                <div class="field"><label class="lbl">Address</label><input class="input" name="address" maxlength="255" value="{{ $s->address ?? '' }}"></div>
                <input type="hidden" name="is_active" value="0">
                <label class="check"><input type="checkbox" name="is_active" value="1" @checked($s->is_active ?? true)> Active</label>
            </div>
            <div class="modal-foot"><button type="button" class="btn" data-close>Cancel</button><button class="btn btn-primary">Save</button></div>
        </form>
    </div>
</div>
