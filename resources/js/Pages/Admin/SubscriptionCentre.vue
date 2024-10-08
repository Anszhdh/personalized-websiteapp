    <script setup>
    import { ref, computed } from 'vue';
    import { Head, usePage } from '@inertiajs/inertia-vue3';
    import AdminLayout from '@/Layouts/AdminLayout.vue';
    import Modal from '@/References/SubscriptionReceipt.vue'; 
    import axios from 'axios';
    import SubscriptionShipping from '@/References/SubscriptionShipping.vue';


    const { props } = usePage();

    const subscriptions = ref(props.value.subscriptions);
    const searchQuery = ref('');
    const filterType = ref('');
    const filterStatus = ref('');
    const today = new Date();

    const todaySubscription = computed(() => {
        const today = new Date().toDateString();
        return subscriptions.value.filter(sub => new Date(sub.start_date).toDateString() === today).length;
    });

    const pendingSubscription = computed(() => {
    return subscriptions.value.filter(sub => {
        // Ensure sub.payments exists and check if any payment has a status of 'pending'
        return sub.payments && sub.payments.some(payment => payment.status === 'pending');
    }).length;
});

    const showModal = ref(false);
    const selectedImage = ref(null);


    const openModal = (image) => {
        selectedImage.value = image;
        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
    };

    //Confirmation state
    const confirmingSubscription = ref(false);
    const confirmAction = ref('');
    const subscriptionToConfirm = ref(null);

    const confirmSubscription = (subscription) => {
        confirmingSubscription.value = true;
        confirmAction.value = 'confirm';
        subscriptionToConfirm.value = subscription;
    }

    const cancelSubscription = (subscription) => {
        confirmingSubscription.value = true;
        confirmAction.value = 'cancel';
        subscriptionToConfirm.value = subscription;
    }

    const formatDate = (dateString) => {
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
    };

    const proceedAction = async (subscriptionId, action) => {
        try {
            const result = await axios.post(`/admin/subscriptions/${subscriptionId}/${action}`);
            if (result.status === 200) {
                // Update subscriptions locally after successful action
                const updatedSubscription = subscriptions.value.find(sub => sub.id === subscriptionId);
                if (updatedSubscription) {
                    if (action === 'confirm') {
                        updatedSubscription.status = 'Confirmed';
                    } else if (action === 'cancel') {
                        updatedSubscription.status = 'Cancelled';
                    }
                }
            } else {
                console.error('Failed to update subscription status:', result.data.error);
            }
        } catch (error) {
            console.error('Error updating subscription status:', error.message);
        }
    };


    const resetConfirmation = () => {
        confirmingSubscription.value = false;
        confirmAction.value = '';
        subscriptionToConfirm.value = null;
    };

    const confirmSubscriptionAction = async () => {
        if (subscriptionToConfirm.value) {
            await proceedAction(subscriptionToConfirm.value.id, 'confirm');
            resetConfirmation();
        }
    };


    const cancelSubscriptionAction = async () => {
        if (subscriptionToConfirm.value) {
            await proceedAction(subscriptionToConfirm.value.id, 'cancel');
            resetConfirmation();
        }
    };

    const editingSubscription = ref(null);
    const showEditModal = ref(false);

    const openEditModal = (subscription) => {
        console.log('Opening edit modal for subscription:', subscription);
        editingSubscription.value = subscription;
        showEditModal.value = true;
    }

    const closeEditModal = () => {
        showEditModal.value = false;
        editingSubscription.value = null;
    }

    const deleteSubscription = async (subscriptionId) => {
        if (confirm('Are you sure you want to delete this subscription?')) {
            try {
                await axios.delete(`/admin/subscriptions/${subscriptionId}`);
                subscriptions.value = subscriptions.value.filter(sub => sub.id !== subscriptionId);
            } catch (error) {
                console.error('Error deleting subscription:', error);
            }
        }
    };
    const generatePDF = async () => {
        try {
            const response = await axios.get('/admin/subscriptions/pdf', {
                responseType: 'blob' // Ensure response type is blob for file download
            });
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', 'subscriptions.pdf');
            document.body.appendChild(link);
            link.click();
            link.remove();
        } catch (error) {
            console.error('Error generating PDF:', error);
        }
    }

    const invoiceIconUrl = computed(() => {
        // Assuming you have a route or endpoint to fetch the icon URL
        return '/invoice.png'; // Replace with your actual dynamic URL
    });
    const downloadInvoice = async (subscriptionId) => {
        try {
            const result = await axios.get(`/admin/subscription/${subscriptionId}/invoice`, { responseType: 'blob' });
            const url = window.URL.createObjectURL(new Blob([result.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `invoice_${subscriptionId}.pdf`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } catch (error) {
            console.error('Error downloading invoice:', error);
        }
    };

    // Computed property for filtered subscriptions based on search query and filter type
    const filteredSubscriptions = computed(() => {
    let filtered = subscriptions.value;

    // Filter by search query
    if (searchQuery.value.trim()) {
        const searchTerm = searchQuery.value.toLowerCase();
        filtered = filtered.filter(subscription => 
            subscription.user.name.toLowerCase().includes(searchTerm) ||
            subscription.id.toString().includes(searchTerm)
        );
    }
  
        // Filter by subscription type
        if (filterType.value) {
        filtered = filtered.filter(subscription => subscription.type === filterType.value);
    }

    // Filter by status (if applicable)
    if (filterStatus.value) {
        filtered = filtered.filter(subscription => subscription.status === filterStatus.value);
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
        <Head title="Subscription Centre" />
        <div class="p-6">
            <h1 class="text-2xl font-semibold text-yellow-950">Subscription Centre</h1>
            <div class="mt-10">
                <div class="flex justify-between mb-10">
                    <div class="bg-[#a08671]/10 p-6 rounded-xl shadow-md pl-5 pr-20">
                        <div class="text-xl font-bold">{{ filteredSubscriptions.length }}</div>
                        <div>Total Subscriptions</div>
                    </div>
                    <div class="bg-[#a08671]/10 p-6 rounded-xl shadow-md pl-5 pr-20">
                        <div class="text-xl font-bold">{{ todaySubscription }}</div>
                        <div>Today's Subscriptions</div>
                    </div>
                    <div class="bg-[#a08671]/10 p-6 rounded-xl shadow-md pl-5 pr-20">
                        <div class="text-xl font-bold">{{ pendingSubscription }}</div>
                        <div>Pending Subscriptions</div>
                    </div>
                </div>
                    <div class="mb-4">
                        <button @click="generatePDF" class="bg-blue-500 text-white px-4 py-2 rounded">Download PDF</button>
                    </div>
                    <div class="flex justify-between mb-4">
                        <div>
                            <input v-model.trim="searchQuery" type="text" placeholder="Search by Name or ID" class="border border-gray-300 px-2 py-1 rounded-md">
                            <button @click="clearSearch" class="ml-2 bg-gray-300 px-2 py-1 rounded-md">Clear</button>
                        </div>
                        <div class="flex items-center space-x-2">
                            <p class="mr-2">Filter</p>   
                        <!-- Filter by subscription type -->
                        <select v-model="filterType" @change="applyFilters" class="px-6 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                            <option value="">All Types</option>
                            <option value="Monthly">Monthly</option>
                            <option value="Weekly">Weekly</option>
                            <option value="Bi-Weekly">Bi-Weekly</option>
                        </select>
                        <!-- Filter by Status -->
                        <select v-model="filterStatus" @change="applyFilters" class="ml-6 px-7 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead class="bg-[#dad8d7] text-black">
                            <tr>
                                <th class="py-2 px-4 border-b text-center">ID#</th>
                                <th class="py-2 px-4 border-b text-center">Name</th>
                                <th class="py-2 px-4 border-b text-center">Subscription Type</th>
                                <th class="py-2 px-4 border-b text-center">Subscription Start</th>
                                <th class="py-2 px-4 border-b text-center">Subscription End</th>
                                <th class="py-2 px-4 border-b text-center">Value</th>
                                <th class="py-2 px-4 border-b text-center">References</th>
                                <th class="py-2 px-4 border-b text-center">Status</th>
                                <th class="py-2 px-4 border-b text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="subscription in filteredSubscriptions" :key="subscription.id" class="text-center align-middle">
                                <td class="py-2 px-4 border-b">{{ subscription.id }}</td>
                                <td class="py-2 px-4 border-b">{{ subscription.user.name }}</td>
                                <td class="py-2 px-4 border-b">{{ subscription.type }}</td>
                                <td class="py-2 px-4 border-b">{{ formatDate(subscription.start_date) }}</td>
                                <td class="py-2 px-4 border-b">{{ formatDate(subscription.end_date) }}</td>
                                <td class="py-2 px-4 border-b">RM {{ subscription.price }}</td>
                                <td class="py-2 px-4 border-b items-center flex space-x-4">
                                    <button 
                                        @click="openModal(subscription.payments.length > 0 ? subscription.payments[0].payment_proof_url : null)" 
                                        class="flex items-center space-x-1 text-blue-500 underline" title="View Receipt"
                                        :disabled="!subscription.payments.length || !subscription.payments[0].payment_proof_url">
                                        <!-- Replace this with your actual SVG icon markup -->
                                        <img src="/receipt.png" alt="View Receipt Icon" class="h-6 w-6 inline-block">
                                    </button>
                                    <button @click="downloadInvoice(subscription.id)" class="text-blue-500 underline" title="Download Invoice" v-if="subscription.payments[0].status === 'Confirmed'">
                                        <img :src="invoiceIconUrl" alt="Download Invoice" class="h-6 w-6 inline-block">
                                    </button>
                                </td>
                                <td class="py-2 px-4 border-b">{{ subscription.status }}</td>
                                <td class="py-2 px-4 border-b">
                                    <template v-if="subscription.payment_ver == 0 && subscription.status === 'active'">
                                        <span @click="confirmSubscription(subscription)" class="cursor-pointer text-green-600 hover:text-green-900" title="Confirm Subscription">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                        <span @click="cancelSubscription(subscription)" class="cursor-pointer text-red-600 hover:text-red-900" title="Cancel Subscription">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </span>
                                    </template>
                                    <template v-else-if="subscription.payment_ver != 0 && subscription.status === 'active'">
                                        <button 
                                            @click="openEditModal(subscription)" 
                                            class="bg-green-500 text-white px-2 py-1 mr-4 rounded">
                                            Edit
                                        </button>
                                        <button 
                                            @click="deleteSubscription(subscription.id)" 
                                            class="bg-red-500 text-white px-2 py-1 rounded">
                                            Delete
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button 
                                            @click="deleteSubscription(subscription.id)" 
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
    </AdminLayout>

    <!-- Modal Component -->
    <Modal :visible="showModal" :image-src="selectedImage" @close="closeModal" />
    
    <!-- Include SubscriptionShipping Modal -->
    <SubscriptionShipping
        v-if="showEditModal"
        :subscription="editingSubscription"
        @close="closeEditModal"
        @shipping-updated="refreshShippingTable"
    />

    <!-- Confirmation Dialog -->
    <div v-if="confirmingSubscription" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white p-4 rounded-lg shadow-lg">
            <p class="mb-4">Are you sure you want to {{ confirmAction === 'confirm' ? 'confirm' : 'cancel' }} this subscription?</p>
            <div class="flex justify-between">
                <button @click="confirmAction === 'confirm' ? confirmSubscriptionAction() : cancelSubscriptionAction()" class="bg-green-500 text-white px-4 py-2 rounded mr-2">Yes</button>
                <button @click="resetConfirmation" class="bg-red-500 text-white px-4 py-2 rounded ml-2">No</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
    /* Add any necessary styling here */
    </style>
