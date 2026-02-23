import './bootstrap';
import Alpine from 'alpinejs';
import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

Alpine.data('userManagement', () => ({
    search: '',
    users: window.usersData || [],
    currentUser: {},

    // Normalización para búsqueda sin tildes
    normalize(text) {
        return text ? text.toString().normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase() : '';
    },

    get filteredUsers() {
        const q = this.normalize(this.search);
        return this.users.filter(u => 
            this.normalize(u.name).includes(q) || 
            this.normalize(u.username).includes(q) ||
            (u.license_number && u.license_number.includes(q))
        );
    },

    openModal(user = null) {
        this.currentUser = user ? { ...user } : { id: null, name: '', username: '', email: '', profession: '', license_number: '', specialty_number: '' };
        const modal = new window.bootstrap.Modal(document.getElementById('userModal'));
        modal.show();
    }
}));

window.Alpine = Alpine;
Alpine.start();