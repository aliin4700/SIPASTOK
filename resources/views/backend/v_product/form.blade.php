<style>
    .breadlab-admin-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
        padding: 24px;
    }

    .breadlab-preview {
        width: 100%;
        max-width: 240px;
        aspect-ratio: 1 / 1;
        object-fit: cover;
        border: 1px solid #eef0f4;
        border-radius: 8px;
        background: #f8fafc;
    }
</style>

<div class="breadlab-admin-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">{{ $judul }}</h3>
        <a href="{{ route('backend.product.index') }}" class="btn btn-secondary">Kembali</a>
    </div>

    <form action="{{ $action }}" method="post" enctype="multipart/form-data">
        @csrf
        @if($method !== 'post')
        @method($method)
        @endif

        <div class="row">
            <div class="col-md-4 mb-4">
                <label class="font-weight-bold">Foto Produk</label>
                <div class="mb-3">
                    <img src="{{ $product->exists ? $product->image_url : asset('breadlab/logo.png') }}" alt="Preview produk" class="breadlab-preview foto-preview">
                </div>
                <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" onchange="previewProductImage(this)">
                @error('image')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Format JPG, PNG, GIF, atau WEBP. Maksimal 2 MB.</small>
            </div>

            <div class="col-md-8">
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="category_id" class="form-control @error('category_id') is-invalid @enderror">
                        <option value="">Tanpa kategori</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>
                            {{ $category->nama_kategori }}
                        </option>
                        @endforeach
                    </select>
                    @error('category_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Nama Produk</label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control @error('name') is-invalid @enderror" placeholder="Contoh: Garlic Bread">
                    @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Harga</label>
                        <input type="number" name="price" value="{{ old('price', $product->price) }}" min="0" step="100" class="form-control @error('price') is-invalid @enderror" placeholder="8000">
                        @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group col-md-6">
                        <label>Stok</label>
                        <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" min="0" class="form-control @error('stock') is-invalid @enderror" placeholder="45">
                    @error('stock')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Berat (gram)</label>
                        <input type="number" name="weight" value="{{ old('weight', $product->weight ?? 250) }}" min="1" class="form-control @error('weight') is-invalid @enderror" placeholder="250">
                        @error('weight')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group col-md-6">
                        <label>Status</label>
                        <select name="status" class="form-control @error('status') is-invalid @enderror">
                            <option value="draft" @selected(old('status', $product->status ?? 'published') === 'draft')>Draft</option>
                            <option value="published" @selected(old('status', $product->status ?? 'published') === 'published')>Published</option>
                            <option value="inactive" @selected(old('status', $product->status ?? 'published') === 'inactive')>Inactive</option>
                        </select>
                        @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>Detail</label>
                    <textarea name="description" rows="6" class="form-control @error('description') is-invalid @enderror" placeholder="Deskripsi singkat produk">{{ old('description', $product->description) }}</textarea>
                    @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">{{ $button }}</button>
            </div>
        </div>
    </form>
</div>

<script>
    function previewProductImage(input) {
        const preview = document.querySelector('.foto-preview');

        if (!input.files || !input.files[0]) {
            return;
        }

        preview.src = URL.createObjectURL(input.files[0]);
    }
</script>
