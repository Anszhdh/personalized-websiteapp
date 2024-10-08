<script setup>
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/References/OrderReceipt.vue'; 
import OrderShipping from '@/References/OrderShipping.vue';

const { props } = usePage();
const orders = ref(props.value.orders);
const searchQuery = ref('');
const filterType = ref('');
const filterStatus = ref('');
const today = new Date();

const todayOrder = computed(() => {
    const today = new Date().toDateString();
    return orders.value.filter(order => new Date(order.created_at).toDateString() === today).length;
});

const pendingOrder = computed(() => {
    return orders.value.filter(order => order.status === 'Pending').length;
});

const showModal = ref(false);
const selectedImage = ref('');
const flashMessage = ref('');

if (props.value.flash && props.value.flash.success) {
  flashMessage.value = props.value.flash.success;
}

const openModal = (image) => {
    selectedImage.value = image;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

// Confirmation state
const confirmingOrder = ref(false);
const confirmAction = ref('');
const orderToConfirm = ref(null);

const confirmOrder = (order) => {
    confirmingOrder.value = true;
    confirmAction.value = 'confirm';
    orderToConfirm.value = order;
};

const cancelOrder = (order) => {
    confirmingOrder.value = true;
    confirmAction.value = 'cancel';
    orderToConfirm.value = order;
};

const proceedAction = async (orderId, action) => {
    try {
        const result = await axios.post(`/orders/${orderId}/${action}`);
        if (result.status === 200) {
            Inertia.visit(window.location.href); // Correctly use the Inertia object
        } else {
            console.error('Failed to update order status');
        }
    } catch (error) {
        console.error('Error updating order status:', error);
    }
};

const resetConfirmation = () => {
    confirmingOrder.value = false;
    confirmAction.value = '';
    orderToConfirm.value = null;
};

const confirmOrderAction = async () => {
    if (orderToConfirm.value) {
        await proceedAction(orderToConfirm.value.id, 'confirm');
        resetConfirmation();
    }
};

const cancelOrderAction = async () => {
    if (orderToConfirm.value) {
        await proceedAction(orderToConfirm.value.id, 'cancel');
        resetConfirmation();
    }
};

const editingOrder = ref(null);
const showEditModal = ref(false);

const openEditModal = (order) => {
  console.log('Opening edit modal for order:', order); // Debugging line
  editingOrder.value = order;
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
  editingOrder.value = null;
};

const deleteOrder = async (orderId) => {
    if (confirm('Are you sure you want to delete this order?')) {
        try {
            await axios.delete(`/admin/orders/${orderId}`);
            Inertia.visit(window.location.href); // Refresh the page
        } catch (error) {
            console.error('Error deleting order:', error);
        }
    }
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}-${month}-${year}`;
};

const formatPaymentType = (type) => {
  // Replace underscores with spaces and capitalize each word
  return type.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const downloadReport = () => {
    axios.get('/admin/orders/report', { responseType: 'blob' })
        .then(response => {
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', 'orders_report.pdf');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        })
        .catch(error => {
            console.error('Error downloading the report:', error);
        });
};

const receiptIconUrl = computed(() => {
    return '/receipt.png'; // Replace with your actual dynamic URL
});
const invoiceIconUrl = computed(() => {
    return '/invoice.png'; // Replace with your actual dynamic URL
});

const downloadInvoice = async (orderId) => {
    try {
        const result = await axios.get(`/admin/orders/${orderId}/invoice`, { responseType: 'blob' });
        const url = window.URL.createObjectURL(new Blob([result.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `invoice_${orderId}.pdf`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (error) {
        console.error('Error downloading invoice:', error);
    }
};

// Computed property for filtered orders based on search query and filter type
const filteredOrders = computed(() => {
    let filtered = orders.value;

    // Filter by search query
    if (searchQuery.value.trim()) {
        const searchTerm = searchQuery.value.toLowerCase();
        filtered = filtered.filter(order => 
            order.user.name.toLowerCase().includes(searchTerm) ||
            order.id.toString().includes(searchTerm)
        );
    }

    // Filter by payment type
    if (filterType.value) {
        filtered = filtered.filter(order => order.payment.type === filterType.value);
    }

    // Filter by status
    if (filterStatus.value) {
        filtered = filtered.filter(order => order.status === filterStatus.value);
    }

    return filtered;
});

// Method to clear search query
const clearSearch = () => {
    searchQuery.value = '';
};

// Function to apply filters
const applyFilters = () => {
    // Trigger computed property update
};
</script>

<template>
    <AdminLayout>
        <Head title="Order Centre" />
        <div class="p-6 bg-white border-b border-gray-200">
            <div v-if="flashMessage" class="alert alert-success">
            {{ flashMessage }}
            </div>
            <h1 class="text-2xl font-semibold text-yellow-950">Order Centre</h1>
            <div class="mt-10">
                <div class="flex justify-between mb-10">
                    <div class="bg-[#a08671]/10 p-6 rounded-xl shadow-md pl-5 pr-20">
                        <div class="text-xl font-bold">{{ orders.length }}</div>
                        <div>Total Orders</div>
                    </div>
                    <div class="bg-[#a08671]/10 p-6 rounded-xl shadow-md pl-5 pr-20">
                        <div class="text-xl font-bold">{{ todayOrder }}</div>
                        <div>New Orders</div>
                    </div>
                    <div class="bg-[#a08671]/10 p-6 rounded-xl shadow-md pl-5 pr-20">
                        <div class="text-xl font-bold">{{ pendingOrder }}</div>
                        <div>Pending Orders</div>
                    </div>
                </div>
                <button @click="downloadReport" class="bg-blue-500 text-white px-4 py-2 rounded mb-6">Download PDF</button>
                <div class="flex justify-between mb-4">
                    <div>
                        <input v-model.trim="searchQuery" type="text" placeholder="Search by Name or ID" class="border border-gray-300 px-2 py-1 rounded-md">
                        <button @click="clearSearch" class="ml-2 bg-gray-300 px-2 py-1 rounded-md">Clear</button>
                    </div>
                    <div class="flex items-center space-x-2">
                        <p class="mr-2">Filter</p>   
                        <select v-model="filterType" @change="applyFilters" class="px-6 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                            <option value="">All Payment Types</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="qr_code">QR Code</option>
                        </select>
                        <select v-model="filterStatus" @change="applyFilters" class="ml-6 px-7 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200 text-center">
                        <thead class="bg-[#dad8d7]">
                            <tr>
                                <th class="py-2 px-4 border-b text-center">ID#</th>
                                <th class="py-2 px-4 border-b text-center">Name</th>
                                <th class="py-2 px-4 border-b text-center">Payment Type</th>
                                <th class="py-2 px-4 border-b text-center">Amount</th>
                                <th class="py-2 px-4 border-b text-center">Status</th>
                                <th class="py-2 px-4 border-b text-center">Date</th>
                                <th class="py-2 px-4 border-b text-center">References</th>
                                <th class="py-2 px-4 border-b text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in filteredOrders" :key="order.id" class="bg-white hover:bg-gray-100">
                                <td class="py-2 px-4 border-b">{{ order.id }}</td>
                                <td class="py-2 px-4 border-b">{{ order.user.name }}</td>
                                <td class="py-2 px-4 border-b">{{ formatPaymentType(order.payment.type) }}</td>
                                <td class="py-2 px-4 border-b">{{ order.total_price }}</td>
                                <td class="py-2 px-4 border-b">{{ order.status }}</td>
                                <td class="py-2 px-4 border-b">{{ formatDate(order.created_at) }}</td>
                                <td class="py-2 px-4 border-b">
                                    <a :href="order.payment.payment_proof_url" target="_blank" title="View Receipt">
                                        <img :src="receiptIconUrl" alt="View Receipt" class="h-6 w-6 inline-block">
                                    </a>
                                    <button @click="downloadInvoice(order.id)" class="text-blue-500 underline" title="Download Invoice" v-if="order.status === 'Confirmed'">
                                        <img :src="invoiceIconUrl" alt="Download Invoice" class="h-6 ml-5 w-6 inline-block">
                                    </button>
                                </td>
                                <td class="py-2 px-4 border-b">
                                    <template v-if="order.status === 'Pending'">
                                        <span @click="confirmOrder(order)" class="cursor-pointer text-green-600 hover:text-green-900" title="Confirm Order">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                        <span @click="cancelOrder(order)" class="cursor-pointer text-red-600 hover:text-red-900" title="Cancel Order">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </span>
                                    </template>
                                    <template v-else-if="order.status === 'Confirmed'">
                                        <button 
                                            @click="openEditModal(order)" 
                                            class="bg-green-500 text-white px-2 py-1 mr-4 rounded">
                                            Edit
                                        </button>
                                        <button 
                                            @click="deleteOrder(order.id)" 
                                            class="bg-red-500 text-white px-2 py-1 rounded">
                                            Delete
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button 
                                            @click="deleteOrder(order.id)" 
                                            class="bg-red-500 text-white px-2 py-1 rounded">
                                            Delete
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Modal Component -->
        <Modal :visible="showModal" :image-src="selectedImage" @close="closeModal" />
     <!-- Include OrderShipping Modal -->
        <OrderShipping
        v-if="showEditModal"
        :order="editingOrder"
        @close="closeEditModal"
        @shipping-updated="refreshShippingTable"
        />
        <!-- Confirmation Dialog -->
        <div v-if="confirmingOrder" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white p-4 rounded-lg shadow-lg">
                <p class="mb-4">Are you sure you want to {{ confirmAction === 'confirm' ? 'confirm' : 'cancel' }} this order?</p>
                <div class="flex justify-between">
                    <button @click="confirmAction === 'confirm' ? confirmOrderAction() : cancelOrderAction()" class="bg-green-500 text-white px-4 py-2 rounded mr-2">Yes</button>
                    <button @click="resetConfirmation" class="bg-red-500 text-white px-4 py-2 rounded ml-2">No</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Add any necessary styling here */
</style>
