/* ===========================================================
        AutoMaster Pro 2026
        CUSTOMER MODULE
===========================================================*/

document.addEventListener("DOMContentLoaded", function () {

    /* ==========================================
            ADD CUSTOMER MODAL
    ========================================== */

    const addCustomerBtn = document.getElementById("addCustomerBtn");

    const customerModal = document.getElementById("customerModal");

    const closeModal = document.getElementById("closeModal");

    const cancelModal = document.getElementById("cancelModal");

    if (addCustomerBtn) {

        addCustomerBtn.addEventListener("click", function () {

            customerModal.style.display = "flex";

        });

    }

    if (closeModal) {

        closeModal.addEventListener("click", function () {

            customerModal.style.display = "none";

        });

    }

    if (cancelModal) {

        cancelModal.addEventListener("click", function () {

            customerModal.style.display = "none";

        });

    }

    /* ==========================================
            VIEW CUSTOMER MODAL
    ========================================== */

    const viewModal = document.getElementById("viewCustomerModal");
    const closeViewModal = document.getElementById("closeViewModal");
    const closeViewModalBtn = document.getElementById("closeViewModalBtn");
    const viewButtons = document.querySelectorAll(".view-btn");

    viewButtons.forEach(function(button){
        button.addEventListener("click", function(){
            document.getElementById("view_customer_name").value = this.dataset.name;
            document.getElementById("view_phone").value = this.dataset.phone;
            document.getElementById("view_email").value = this.dataset.email || '-';
            document.getElementById("view_city").value = this.dataset.city || '-';
            document.getElementById("view_state").value = this.dataset.state || '-';
            document.getElementById("view_pincode").value = this.dataset.pincode || '-';
            document.getElementById("view_address").value = this.dataset.address || '-';
            document.getElementById("view_status").value = this.dataset.status;
            viewModal.style.display = "flex";
        });
    });

    if(closeViewModal){
        closeViewModal.addEventListener("click", function(){
            viewModal.style.display = "none";
        });
    }

    if(closeViewModalBtn){
        closeViewModalBtn.addEventListener("click", function(){
            viewModal.style.display = "none";
        });
    }

    /* ==========================================
            EDIT CUSTOMER MODAL
    ========================================== */

    const editModal = document.getElementById("editCustomerModal");

    const closeEditModal = document.getElementById("closeEditModal");

    const cancelEditModal = document.getElementById("cancelEditModal");

    const editButtons = document.querySelectorAll(".edit-btn");

    editButtons.forEach(function(button){

        button.addEventListener("click", function(){

            document.getElementById("edit_customer_id").value = this.dataset.id;

            document.getElementById("edit_customer_name").value = this.dataset.name;

            document.getElementById("edit_phone").value = this.dataset.phone;

            document.getElementById("edit_email").value = this.dataset.email;

            document.getElementById("edit_address").value = this.dataset.address;

            document.getElementById("edit_city").value = this.dataset.city;

            document.getElementById("edit_state").value = this.dataset.state;

            document.getElementById("edit_pincode").value = this.dataset.pincode;

            document.getElementById("edit_status").value = this.dataset.status;

            editModal.style.display = "flex";

        });

    });

    if(closeEditModal){

        closeEditModal.addEventListener("click",function(){

            editModal.style.display="none";

        });

    }

    if(cancelEditModal){

        cancelEditModal.addEventListener("click",function(){

            editModal.style.display="none";

        });

    }

    /* ==========================================
            CLOSE WHEN CLICK OUTSIDE
    ========================================== */

    window.addEventListener("click", function (event) {

        if (event.target === customerModal) {

            customerModal.style.display = "none";

        }

        if (event.target === editModal) {

            editModal.style.display = "none";

        }

        if (event.target === viewModal) {

            viewModal.style.display = "none";

        }

    });

});

/* ===========================================================
        FORM VALIDATION & UI ENHANCEMENTS
===========================================================*/

const customerForm = document.getElementById("customerForm");
const editCustomerForm = document.getElementById("editCustomerForm");

/* ---------- Phone Validation ---------- */

function validatePhone(input){

    if(!input) return true;

    input.addEventListener("input",function(){

        this.value=this.value.replace(/\D/g,'');

        if(this.value.length>10){

            this.value=this.value.slice(0,10);

        }

    });

}

validatePhone(document.querySelector('input[name="phone"]'));
validatePhone(document.getElementById("edit_phone"));

/* ---------- Email Validation ---------- */

function validateEmail(form){

    if(!form) return;

    form.addEventListener("submit",function(e){

        const email=form.querySelector('input[type="email"]');

        if(email && email.value.trim()!=""){

            const pattern=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if(!pattern.test(email.value)){

                alert("Please enter a valid email address.");

                email.focus();

                e.preventDefault();

            }

        }

    });

}

validateEmail(customerForm);
validateEmail(editCustomerForm);

/* ---------- Prevent Double Click ---------- */

/* ---------- Auto Hide Alerts ---------- */

const alertBox=document.querySelector(".alert");

if(alertBox){

    setTimeout(function(){

        alertBox.style.transition="0.5s";

        alertBox.style.opacity="0";

        setTimeout(function(){

            alertBox.remove();

        },500);

    },3000);

}