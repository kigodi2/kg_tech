{{-- Reusable Data Manager Alpine.js Component --}}
<script>
function dataManager(config) {
    return {
        items: [],
        filteredItems: [],
        search: '',
        editingId: null,
        formData: {},
        loading: false,
        modalOpen: false,
        
        // Configuration
        apiUrl: config.apiUrl,
        columns: config.columns, // Array of { key, label, type }
        formFields: config.formFields, // Array of { name, label, type, required }
        
        async init() {
            await this.loadItems();
        },

        async loadItems() {
            this.loading = true;
            try {
                const response = await fetch(this.apiUrl);
                const data = await response.json();
                this.items = data.data || data || [];
                this.filteredItems = this.items;
            } catch (error) {
                console.error('Error loading items:', error);
                this.showMessage('Error loading data', 'error');
            } finally {
                this.loading = false;
            }
        },

        filterItems() {
            const term = this.search.toLowerCase();
            this.filteredItems = this.items.filter(item => {
                return Object.values(item).some(val => 
                    String(val).toLowerCase().includes(term)
                );
            });
        },

        openAddModal() {
            this.editingId = null;
            this.formData = {};
            this.formFields.forEach(field => {
                this.formData[field.name] = '';
            });
            this.modalOpen = true;
        },

        openEditModal(item) {
            this.editingId = item.id;
            this.formData = { ...item };
            this.modalOpen = true;
        },

        async saveItem() {
            try {
                const url = this.editingId ? `${this.apiUrl}/${this.editingId}` : this.apiUrl;
                const method = this.editingId ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.formData),
                });

                const data = await response.json();
                
                if (response.ok) {
                    this.showMessage(
                        this.editingId ? 'Updated successfully' : 'Added successfully',
                        'success'
                    );
                    this.modalOpen = false;
                    await this.loadItems();
                } else {
                    this.showMessage(data.message || 'Error saving', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showMessage('Error saving', 'error');
            }
        },

        async deleteItem(id) {
            if (!confirm('Are you sure?')) return;

            try {
                const response = await fetch(`${this.apiUrl}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                if (response.ok) {
                    this.showMessage('Deleted successfully', 'success');
                    await this.loadItems();
                } else {
                    this.showMessage('Error deleting', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showMessage('Error deleting', 'error');
            }
        },

        async exportCSV() {
            try {
                const headers = this.columns.map(c => c.label);
                const rows = this.filteredItems.map(item => 
                    this.columns.map(c => `"${item[c.key] || ''}"`)
                );
                
                const csv = [headers, ...rows].map(r => r.join(',')).join('\n');
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `export_${Date.now()}.csv`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                this.showMessage('Exported successfully', 'success');
            } catch (error) {
                console.error('Error:', error);
                this.showMessage('Error exporting', 'error');
            }
        },

        async importCSV(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch(`${this.apiUrl}/import`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                });

                const data = await response.json();
                
                if (response.ok) {
                    this.showMessage(`Imported ${data.count || 0} records successfully`, 'success');
                    await this.loadItems();
                } else {
                    this.showMessage(data.message || 'Error importing', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showMessage('Error importing', 'error');
            }
        },

        downloadTemplate() {
            const headers = this.columns.map(c => c.label);
            const csv = headers.join(',');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `template_${Date.now()}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            this.showMessage('Template downloaded', 'success');
        },

        showMessage(message, type) {
            const alertDiv = document.createElement('div');
            const bgClass = type === 'success' ? 'bg-green-100 text-green-700 border-green-300' : 'bg-red-100 text-red-700 border-red-300';
            
            alertDiv.className = `fixed top-20 right-8 ${bgClass} p-4 rounded-lg border max-w-md z-50 animate-in fade-in`;
            alertDiv.textContent = message;
            
            document.body.appendChild(alertDiv);
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        },
    };
}
</script>
