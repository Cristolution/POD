# Design Editor (Canvas) — Handoff Plan

> **For:** Less-intelligent AI model continuing work in a new session.
> **Goal:** Designers can create new designs via a browser canvas editor. Features: x/y positioning, scaling, blend modes, invert, optional mockup overlay from base assets, optional upload-own-design. Mockup is **optional** — designer can keep the design empty.

---

## Context

- **Repo:** `c:/Users/Crist/Desktop/ADISC/POD`
- **Branch:** `local-smart-shot`
- **Stack:** Laravel 13, Alpine.js (already in project), Tailwind v4, Filament v4
- **Existing design flow:** Designers create designs via `php artisan tinker` or via seeded data only. There's NO web UI for designers to create designs.
- **Design model:** `app/Models/Design.php` has `title`, `description`, `category_id`, `designer_id`, `status`, `tags`, `mappings`. No `mockup_id` or `original_image_path` column yet.
- **Media model:** `app/Models/Media.php` — designs have `media` rows for `mockup` (preview) and `print_file` (high-res).
- **Designer routes:** `routes/web.php` has designer routes under `Route::middleware(['auth', 'role:designer'])`. Add new routes for editor.
- **Mockup assets (the user-provided base mockups):** `C:\Users\Crist\Desktop\enhanced mockup data assets\base\` contains 12 PNG files (black_cap, black_hoodie, black_mug, black_tote_bag, black_tshirt, canvas, circle_white_sticker, framed_white_poster, grey_long_sleeve, white_bottle, white_hoodie, pod1_grey_hoodie). Copy them to `storage/app/public/products/base/` (gitignored) so the editor can list them.
- **Existing JS:** project uses Alpine.js. No canvas library yet — use vanilla HTML5 Canvas API (no new deps).

---

## What to build (5 tasks)

### Task 1: Schema additions

**Files:**
- New migration: `database/migrations/<TIMESTAMP>_add_canvas_fields_to_designs.php`
- Modify: `app/Models/Design.php` — add new fillable

**New columns on `designs`:**
- `mockup_id` (foreignUuid, nullable, constrained to `media.id`) — the base mockup overlay chosen at creation time
- `canvas_state` (json, nullable) — stores the editor state (x, y, scale, blend mode, invert flag) as JSON for editing later
- `preview_path` (string, nullable) — server-side rendered preview if needed (we render client-side, so this can be NULL or store the rendered PNG)

Actually for MVP you can SKIP `preview_path` and just regenerate the preview on each edit. Add `mockup_id` + `canvas_state` only.

```php
Schema::table('designs', function (Blueprint $t) {
    $t->foreignUuid('mockup_id')->nullable()->constrained('media')->nullOnDelete();
    $t->json('canvas_state')->nullable();
});
```

---

### Task 2: Copy mockup assets + controller for listing

**Files:**
- New: `app/Console/Commands/CopyBaseMockups.php` (one-time copy command)
- New: `app/Http/Controllers/Designer/DesignEditorController.php`
- New route: `GET /designer/designs/new` and `/{locale}/designer/designs/new`

**Asset copy command:** Run this once. Idempotent (skips if file exists):
```php
// Reads C:/Users/Crist/Desktop/enhanced mockup data assets/base/*.png
// Copies to storage/app/public/products/base/
// Also creates Media records with collection_name='base_mockup' for the editor to list
```

Run via `php artisan mockups:install` after deploy/fresh clone.

**Controller:**
- `new()` returns view with:
  - List of base mockups (from Media where collection_name='base_mockup')
  - File upload input
  - "Skip mockup" option (empty design)

The view uses Alpine.js to switch between mockup-selection / file-upload / skip-mockup modes.

---

### Task 3: Canvas editor view (Alpine.js + HTML5 Canvas)

**Files:**
- New: `resources/views/designer/designs/editor.blade.php`
- New: `resources/js/canvas-editor.js` (Alpine.js component or standalone)

**View structure:**
```html
<form method="POST" action="...">
    @csrf
    <input type="text" name="title" required>
    <textarea name="description"></textarea>
    <select name="category_id"><!-- categories --></select>

    <!-- 3 tabs: Upload | Mockup | Skip -->
    <div x-data="{ mode: 'mockup' }">
        <button @click="mode='upload'">Upload</button>
        <button @click="mode='mockup'">Pick mockup</button>
        <button @click="mode='skip'">Skip</button>

        <!-- Upload mode: file input -->
        <input type="file" name="upload" x-show="mode==='upload'" accept="image/*">

        <!-- Mockup mode: grid of base mockups -->
        <div x-show="mode==='mockup'" class="grid grid-cols-3 gap-2">
            @foreach ($baseMockups as $m)
                <label>
                    <input type="radio" name="mockup_id" value="{{ $m->id }}">
                    <img src="{{ Storage::disk('public')->url($m->file_path) }}">
                </label>
            @endforeach
        </div>

        <!-- Skip mode: no mockup -->
        <div x-show="mode==='skip'">Design will be saved without a mockup overlay.</div>
    </div>

    <!-- Canvas editor (only shown if upload or mockup selected) -->
    <canvas id="design-canvas" width="800" height="800"></canvas>

    <!-- Editor controls -->
    <div x-data="canvasEditor()" x-init="init()">
        <button @click="resetTransform()">Reset</button>

        <label>X: <input type="range" x-model.number="x" min="0" max="800"></label>
        <label>Y: <input type="range" x-model.number="y" min="0" max="800"></label>
        <label>Scale: <input type="range" x-model.number="scale" min="0.1" max="3" step="0.1"></label>

        <label>Blend mode:
            <select x-model="blendMode">
                <option value="source-over">Normal</option>
                <option value="multiply">Multiply</option>
                <option value="screen">Screen</option>
                <option value="overlay">Overlay</option>
                <option value="darken">Darken</option>
                <option value="lighten">Lighten</option>
                <option value="color-dodge">Color dodge</option>
                <option value="color-burn">Color burn</option>
                <option value="hard-light">Hard light</option>
                <option value="soft-light">Soft light</option>
                <option value="difference">Difference</option>
                <option value="exclusion">Exclusion</option>
                <option value="hue">Hue</option>
                <option value="saturation">Saturation</option>
                <option value="color">Color</option>
                <option value="luminosity">Luminosity</option>
            </select>
        </label>

        <label>
            <input type="checkbox" x-model="invert"> Invert colors
        </label>

        <input type="hidden" name="canvas_state" :value="JSON.stringify({x, y, scale, blendMode, invert})">
    </div>

    <button type="submit">Save design</button>
</form>
```

**canvas-editor.js** (Alpine.js component):
```javascript
function canvasEditor() {
    return {
        canvas: null,
        ctx: null,
        designImage: null,  // uploaded or mockup overlay
        x: 400, y: 400, scale: 1, blendMode: 'multiply', invert: false,

        init() {
            this.canvas = document.getElementById('design-canvas');
            this.ctx = this.canvas.getContext('2d');
            this.draw();
        },

        resetTransform() {
            this.x = 400; this.y = 400; this.scale = 1;
            this.invert = false;
            this.blendMode = 'multiply';
            this.draw();
        },

        draw() {
            // clear canvas
            this.ctx.clearRect(0, 0, 800, 800);
            // draw mockup overlay (background) if a mockup is selected
            // draw user's design on top with blend mode + invert
            // ... actual draw logic
        },
    };
}
```

This is the most complex JS in the project. If you can't write it from scratch, **simplify**: just show the design image at (x, y) with scale and blend mode, no invert. Invert can be a later task.

---

### Task 4: Save endpoint

**Files:**
- Modify: `app/Http/Controllers/Designer/DesignEditorController.php`
- New: `app/Http/Requests/Designer/StoreDesignRequest.php`

**`store()` method:**
1. Validate request (title, description, category_id, optional file, optional mockup_id)
2. Create Design record with `designer_id = auth user`, `status = 'draft'`
3. If file uploaded: save to `storage/app/public/designs/{design_id}/uploaded.png`, create Media record (collection_name='mockup')
4. If mockup_id selected: create Media record with `product_template_id = null` (or link to mockup)
5. If canvas_state provided: save to Design.canvas_state
6. Redirect to designer dashboard with success message

**Routes (add to BOTH unprefixed and `{locale}` groups):**
```php
Route::middleware(['auth', 'role:designer'])->prefix('designer')->name('designer.')->group(function () {
    Route::get('/designs/new', [DesignEditorController::class, 'new'])->name('designs.new');
    Route::post('/designs', [DesignEditorController::class, 'store'])->name('designs.store');
});
```

(Existing designer routes are at the same level — append to the existing `Route::middleware(['auth', 'role:designer'])` group.)

---

### Task 5: Tests + commit

**Tests:**
- `tests/Feature/Designer/DesignEditorTest.php`:
  - GET `/designer/designs/new` returns 200 for designer
  - GET returns 302 (redirect) for non-designer
  - POST with mockup_id creates Design + Media
  - POST with file upload creates Design + Media
  - POST with neither (skip mode) creates Design with no Media
  - canvas_state is saved correctly
  - Validation errors on missing title

**Test commands:**
```bash
php artisan test --compact tests/Feature/Designer/DesignEditorTest.php
php artisan test --compact  # full suite
vendor/bin/pint --dirty --format agent
```

**Commit:**
```bash
git add app/ database/ resources/ routes/ lang/
git commit -m "feat(designer): canvas editor for creating designs

- Design model: add mockup_id + canvas_state columns
- CopyBaseMockups artisan command (one-time mockup install)
- DesignEditorController: new + store endpoints
- editor.blade.php: 3 modes (upload/mockup/skip) + canvas controls
- canvas-editor.js: x/y/scale/blend modes/invert (Alpine.js component)
- Mockup is OPTIONAL — designer can skip and save empty design
- Lang keys added for new UI"
```

---

## Constraints

- **Mockup is OPTIONAL.** If designer picks "Skip", save Design with NO mockup. Don't error.
- **Canvas state stored as JSON** — must round-trip cleanly (load → edit → save without losing data).
- **All controls in `lang/en.json` etc.** — `__('designer_canvas_x')`, etc. Don't hardcode English.
- **Working tree has pre-existing uncommitted files.** Use `git add <specific paths>` only.
- **Don't break existing 583+ tests.**
- **Existing route group:** `Route::middleware(['auth', 'role:designer'])` — append new routes inside that group, don't create a parallel group.

---

## Done when

- [ ] All 5 tasks complete
- [ ] `php artisan test --compact` green
- [ ] `vendor/bin/pint --format agent` passes
- [ ] Single commit on `local-smart-shot`
- [ ] Manually: login as `lana@designstudio.test` → visit `/designer/designs/new` → pick a mockup → drag/scale → save → design appears in `/designer/dashboard`
