@extends('layouts.admin')

@section('title', 'Dashboard')

@section('header-title', 'Dashboard')

@section('header-actions')
<a href="{{ route('admin.products.create') }}" class="btn btn-secondary btn-sm">
    <i class="fas fa-plus"></i>
    Add Product
</a>
@endsection

@section('content')
<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <div class="admin-card text-center">
        <h3 style="font-size: 16px; color: var(--muted-text); margin-bottom: 8px;">Total Products</h3>
        <h2 style="font-size: 36px; color: var(--dark-green); margin: 16px 0;">248</h2>
        <p style="color: var(--muted-text); font-size: 14px;">Active products in store</p>
    </div>
    
    <div class="admin-card text-center">
        <h3 style="font-size: 16px; color: var(--muted-text); margin-bottom: 8px;">Total Orders</h3>
        <h2 style="font-size: 36px; color: var(--gold); margin: 16px 0;">1,429</h2>
        <p style="color: var(--muted-text); font-size: 14px;">Orders this month</p>
    </div>
    
    <div class="admin-card text-center">
        <h3 style="font-size: 16px; color: var(--muted-text); margin-bottom: 8px;">Revenue</h3>
        <h2 style="font-size: 36px; color: var(--dark-green); margin: 16px 0;">$28,492</h2>
        <p style="color: var(--muted-text); font-size: 14px;">This month</p>
    </div>
    
    <div class="admin-card text-center">
        <h3 style="font-size: 16px; color: var(--muted-text); margin-bottom: 8px;">Customers</h3>
        <h2 style="font-size: 36px; color: var(--dark-green); margin: 16px 0;">3,847</h2>
        <p style="color: var(--muted-text); font-size: 14px;">Total registered</p>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Recent Orders</h3>
        <p>Latest orders from customers</p>
    </div>
    <div class="admin-table">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>#1234</td>
                    <td>Alice Johnson</td>
                    <td>Organic Tomatoes</td>
                    <td>$24.99</td>
                    <td><span style="background: var(--dark-green); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Completed</span></td>
                    <td>2024-01-15</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-outline btn-sm">View</button>
                            <button class="btn btn-ghost btn-sm">Edit</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>#1235</td>
                    <td>Bob Smith</td>
                    <td>Fresh Lettuce</td>
                    <td>$12.50</td>
                    <td><span style="background: var(--gold); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Processing</span></td>
                    <td>2024-01-15</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-outline btn-sm">View</button>
                            <button class="btn btn-ghost btn-sm">Edit</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>#1236</td>
                    <td>Carol White</td>
                    <td>Green Peppers</td>
                    <td>$18.75</td>
                    <td><span style="background: var(--muted-text); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Pending</span></td>
                    <td>2024-01-14</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-outline btn-sm">View</button>
                            <button class="btn btn-ghost btn-sm">Edit</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>#1237</td>
                    <td>David Brown</td>
                    <td>Carrots</td>
                    <td>$15.00</td>
                    <td><span style="background: var(--dark-green); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Completed</span></td>
                    <td>2024-01-14</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-outline btn-sm">View</button>
                            <button class="btn btn-ghost btn-sm">Edit</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>#1238</td>
                    <td>Eve Davis</td>
                    <td>Cucumbers</td>
                    <td>$22.25</td>
                    <td><span style="background: var(--gold); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Processing</span></td>
                    <td>2024-01-13</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-outline btn-sm">View</button>
                            <button class="btn btn-ghost btn-sm">Edit</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Button Examples -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Button Examples</h3>
        <p>Different button styles with the Fati Market theme</p>
    </div>
    
    <div style="display: grid; gap: 24px;">
        <div>
            <h4 style="margin-bottom: 16px; color: var(--dark-text);">Primary Buttons</h4>
            <div class="btn-group">
                <button class="btn btn-primary">Default</button>
                <button class="btn btn-primary btn-sm">Small</button>
                <button class="btn btn-primary btn-lg">Large</button>
                <button class="btn btn-primary" disabled>Disabled</button>
            </div>
        </div>
        
        <div>
            <h4 style="margin-bottom: 16px; color: var(--dark-text);">Secondary Buttons</h4>
            <div class="btn-group">
                <button class="btn btn-secondary">Default</button>
                <button class="btn btn-secondary btn-sm">Small</button>
                <button class="btn btn-secondary btn-lg">Large</button>
            </div>
        </div>
        
        <div>
            <h4 style="margin-bottom: 16px; color: var(--dark-text);">Outline Buttons</h4>
            <div class="btn-group">
                <button class="btn btn-outline">Default</button>
                <button class="btn btn-outline btn-sm">Small</button>
                <button class="btn btn-outline btn-lg">Large</button>
            </div>
        </div>
        
        <div>
            <h4 style="margin-bottom: 16px; color: var(--dark-text);">Ghost Buttons</h4>
            <div class="btn-group">
                <button class="btn btn-ghost">Default</button>
                <button class="btn btn-ghost btn-sm">Small</button>
                <button class="btn btn-ghost btn-lg">Large</button>
            </div>
        </div>
        
        <div>
            <h4 style="margin-bottom: 16px; color: var(--dark-text);">Icon Buttons</h4>
            <div class="btn-group">
                <button class="btn btn-primary"><i class="fas fa-plus"></i> Add New</button>
                <button class="btn btn-secondary"><i class="fas fa-edit"></i> Edit</button>
                <button class="btn btn-outline"><i class="fas fa-trash"></i> Delete</button>
                <button class="btn btn-ghost"><i class="fas fa-download"></i> Download</button>
            </div>
        </div>
    </div>
</div>

<!-- Form Example -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Add New Product</h3>
        <p>Fill in the product details</p>
    </div>
    
    <form>
        <div class="form-group">
            <label class="form-label" for="product-name">Product Name <span style="color: var(--gold);">*</span></label>
            <input type="text" id="product-name" class="form-input" placeholder="Enter product name" required>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="category">Category <span style="color: var(--gold);">*</span></label>
            <select id="category" class="form-select" required>
                <option value="">Select a category</option>
                <option value="vegetables">Vegetables</option>
                <option value="fruits">Fruits</option>
                <option value="dairy">Dairy</option>
                <option value="grains">Grains</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="price">Price ($) <span style="color: var(--gold);">*</span></label>
            <input type="number" id="price" class="form-input" placeholder="0.00" step="0.01" required>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" class="form-textarea" placeholder="Enter product description"></textarea>
            <small style="color: var(--muted-text); margin-top: 4px; display: block;">Provide a detailed description of the product</small>
        </div>
        
        <div class="btn-group" style="margin-top: 24px;">
            <button type="submit" class="btn btn-primary">Save Product</button>
            <button type="button" class="btn btn-ghost">Cancel</button>
        </div>
    </form>
</div>
@endsection
