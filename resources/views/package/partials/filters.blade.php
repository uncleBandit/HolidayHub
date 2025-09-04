<div>
    <input wire:model.debounce.300ms="search"
           type="text"
           placeholder="Search packages..."
           class="w-full border rounded-xl px-3 py-2 focus:ring focus:border-blue-400"/>
</div>

<div>
    <label class="block text-sm font-medium mb-1">Sort by</label>
    <select wire:model="sortBy" class="w-full border rounded-xl px-3 py-2">
        <option value="newest">Newest</option>
        <option value="price_low">Price: Low to High</option>
        <option value="price_high">Price: High to Low</option>
    </select>
</div>

<div>
    <label class="block text-sm font-medium mb-1">Country</label>
    <input wire:model="country" type="text" placeholder="e.g. Kenya"
           class="w-full border rounded-xl px-3 py-2"/>
</div>

<div class="grid grid-cols-2 gap-2">
    <input wire:model="minPrice" type="number" placeholder="Min $"
           class="border rounded-xl px-3 py-2"/>
    <input wire:model="maxPrice" type="number" placeholder="Max $"
           class="border rounded-xl px-3 py-2"/>
</div>
