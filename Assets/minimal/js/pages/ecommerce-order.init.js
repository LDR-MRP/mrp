// Formatear fecha
var str_dt = function (e) {
    var e = new Date(e),
        t = (e.getHours() + ":" + e.getMinutes()).split(":"),
        n = t[0],
        a = n >= 12 ? "PM" : "AM";

    n = (n %= 12) || 12;
    t = (t = t[1]) < 10 ? "0" + t : t;

    month = "" + [
        "Jan", "Feb", "Mar", "Apr", "May", "Jun",
        "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
    ][e.getMonth()];

    day = "" + e.getDate();
    year = e.getFullYear();

    month.length < 2 && (month = "0" + month);
    day = day.length < 2 ? "0" + day : day;

    return [
        day + " " + month + "," + year +
        " <small class='text-muted'>" +
        n + ":" + t + " " + a +
        "</small>"
    ];
};

// Selects de status y payment con Choices
var isChoiceEl = document.getElementById("idStatus"),
    choices = new Choices(isChoiceEl, { searchEnabled: !1 }),
    isPaymentEl = document.getElementById("idPayment");

choices = new Choices(isPaymentEl, { searchEnabled: !1 });

// Checkbox de seleccionar todo
var checkAll = document.getElementById("checkAll"),
    perPage = (
        checkAll && (checkAll.onclick = function () {
            var e = document.querySelectorAll('.form-check-all input[type="checkbox"]'),
                t = document.querySelectorAll('.form-check-all input[type="checkbox"]:checked').length;

            for (var a = 0; a < e.length; a++) {
                e[a].checked = this.checked;
                if (e[a].checked)
                    e[a].closest("tr").classList.add("table-active");
                else
                    e[a].closest("tr").classList.remove("table-active");
            }

            document.getElementById("remove-actions").style.display = 0 < t ? "none" : "block";
        }),
        8
    ),
    editlist = !1;

// Opciones de List.js
var options = {
        valueNames: [
            "id",
            "customer_name",
            "product_name",
            "date",
            "amount",
            "payment",
            "status"
        ],
        page: perPage,
        pagination: !0,
        plugins: [ListPagination({ left: 2, right: 2 })]
    },
    orderList = new List("orderList", options).on("updated", function (e) {
        // Mostrar/ocultar "noresult"
        0 == e.matchingItems.length
            ? document.getElementsByClassName("noresult")[0].style.display = "block"
            : document.getElementsByClassName("noresult")[0].style.display = "none";

        var t = 1 == e.i,
            a = e.i > e.matchingItems.length - e.page;

        // Quitar disabled prev/next
        document.querySelector(".pagination-prev.disabled") &&
            document.querySelector(".pagination-prev.disabled").classList.remove("disabled");
        document.querySelector(".pagination-next.disabled") &&
            document.querySelector(".pagination-next.disabled").classList.remove("disabled");

        // Deshabilitar si está en primera/última página
        t && document.querySelector(".pagination-prev").classList.add("disabled");
        a && document.querySelector(".pagination-next").classList.add("disabled");

        // Mostrar/ocultar contenedor de paginación
        e.matchingItems.length <= perPage
            ? document.querySelector(".pagination-wrap").style.display = "none"
            : document.querySelector(".pagination-wrap").style.display = "flex";

        // Forzar click en la primera página si coincide con perPage
        e.matchingItems.length == perPage &&
            document
                .querySelector(".pagination.listjs-pagination")
                .firstElementChild.children[0].click();

        // Volver a controlar noresult
        0 < e.matchingItems.length
            ? document.getElementsByClassName("noresult")[0].style.display = "none"
            : document.getElementsByClassName("noresult")[0].style.display = "block";
    });

// Petición AJAX inicial
const xhttp = new XMLHttpRequest();
xhttp.onload = function () {
    var e = JSON.parse(this.responseText);

    Array.from(e).forEach(function (e) {
        orderList.add({
            id:
                '<a href="apps-ecommerce-order-details.html" class="fw-medium link-primary">#VZ' +
                e.id +
                "</a>",
            customer_name: e.customer_name,
            product_name: e.product_name,
            date: str_dt(e.date),
            amount: e.amount,
            payment: e.payment,
            status: isStatus(e.status)
        });

        orderList.sort("id", { order: "desc" });
        refreshCallbacks();
    });

    orderList.remove(
        "id",
        '<a href="apps-ecommerce-order-details.html" class="fw-medium link-primary">#VZ2101</a>'
    );
};
xhttp.open("GET", "assets/json/orders-list.init.json");
xhttp.send();

// Variables de form y modal
var isValue = (
        isCount = (new DOMParser).parseFromString(
            orderList.items.slice(-1)[0]._values.id,
            "text/html"
        )
    ).body.firstElementChild.innerHTML,
    idField = document.getElementById("orderId"),
    customerNameField = document.getElementById("customername-field"),
    productNameField = document.getElementById("productname-field"),
    dateField = document.getElementById("date-field"),
    amountField = document.getElementById("amount-field"),
    paymentField = document.getElementById("payment-field"),
    statusField = document.getElementById("delivered-status"),
    addBtn = document.getElementById("add-btn"),
    editBtn = document.getElementById("edit-btn"),
    removeBtns = document.getElementsByClassName("remove-item-btn"),
    editBtns = document.getElementsByClassName("edit-item-btn"),
    tabEl = (
        refreshCallbacks(),
        document.querySelectorAll('a[data-bs-toggle="tab"]')
    );

// Filtrar por tab (status)
function filterOrder(e) {
    var t = e;

    orderList.filter(function (item) {
        matchData = (new DOMParser).parseFromString(item.values().status, "text/html");
        var status = matchData.body.firstElementChild.innerHTML;

        return "All" == status || "All" == t || status == t;
    });

    orderList.update();
}

// Otro filtro (parece para userList, no orderList)
function updateList() {
    var t = document.querySelector("input[name=status]:checked").value;

    data = userList.filter(function (e) {
        return "All" == t || e.values().sts == t;
    });

    userList.update();
}

// Tabs
Array.from(tabEl).forEach(function (e) {
    e.addEventListener("shown.bs.tab", function (e) {
        filterOrder(e.target.id);
    });
});

// Mostrar modal
document.getElementById("showModal").addEventListener("show.bs.modal", function (e) {
    if (e.relatedTarget.classList.contains("edit-item-btn")) {
        document.getElementById("exampleModalLabel").innerHTML = "Edit Order";
        document.getElementById("showModal").querySelector(".modal-footer").style.display = "block";
        document.getElementById("add-btn").innerHTML = "Update";
    } else if (e.relatedTarget.classList.contains("add-btn")) {
        document.getElementById("modal-id").style.display = "none";
        document.getElementById("exampleModalLabel").innerHTML = "Add Order";
        document.getElementById("showModal").querySelector(".modal-footer").style.display = "block";
        document.getElementById("add-btn").innerHTML = "Add Order";
    } else {
        document.getElementById("exampleModalLabel").innerHTML = "List Order";
        document.getElementById("showModal").querySelector(".modal-footer").style.display = "none";
    }
});

// Reiniciar checkboxes
ischeckboxcheck();

// Al cerrar modal, limpiar campos
document.getElementById("showModal").addEventListener("hidden.bs.modal", function () {
    clearFields();
});

// Volver a aplicar chequeo al hacer click en la lista
document.querySelector("#orderList").addEventListener("click", function () {
    ischeckboxcheck();
});

// Tabla base (no se usa mucho en este snippet)
var table = document.getElementById("orderTable"),
    tr = table.getElementsByTagName("tr"),
    trlist = table.querySelectorAll(".list tr");

// Filtro por status, payment y rango de fecha
function SearchData() {
    var s = document.getElementById("idStatus").value,
        r = document.getElementById("idPayment").value,
        i = document.getElementById("demo-datepicker").value,
        d = i.split(" to ")[0],
        o = i.split(" to ")[1];

    orderList.filter(function (e) {
        var t = (
                matchData = (new DOMParser).parseFromString(e.values().status, "text/html")
            ).body.firstElementChild.innerHTML,
            a = !1,
            n = !1,
            l = !1;

        a = "all" == t || "all" == s || t == s;
        n = "all" == e.values().payment || "all" == r || e.values().payment == r;
        l =
            new Date(e.values().date.slice(0, 12)) >= new Date(d) &&
            new Date(e.values().date.slice(0, 12)) <= new Date(o);

        return (
            (a && n && l) ||
            (a && n && "" == i ? a && n : n && l && "" == i ? n && l : void 0)
        );
    });

    orderList.update();
}

// Alta/edición de registros desde formulario
var count = 13,
    forms = document.querySelectorAll(".tablelist-form"),
    example = (
        Array.prototype.slice.call(forms).forEach(function (a) {
            a.addEventListener(
                "submit",
                function (e) {
                    var t;
                    if (a.checkValidity()) {
                        e.preventDefault();

                        if (
                            "" === customerNameField.value ||
                            "" === productNameField.value ||
                            "" === dateField.value ||
                            "" === amountField.value ||
                            "" === paymentField.value ||
                            editlist
                        ) {
                            // EDITAR
                            if (
                                "" !== customerNameField.value &&
                                "" !== productNameField.value &&
                                "" !== dateField.value &&
                                "" !== amountField.value &&
                                "" !== paymentField.value &&
                                editlist
                            ) {
                                t = orderList.get({ id: idField.value });

                                Array.from(t).forEach(function (e) {
                                    isid = (new DOMParser).parseFromString(
                                        e._values.id,
                                        "text/html"
                                    );

                                    if (isid.body.firstElementChild.innerHTML == itemId) {
                                        e.values({
                                            id:
                                                '<a href="javascript:void(0);" class="fw-medium link-primary">' +
                                                idField.value +
                                                "</a>",
                                            customer_name: customerNameField.value,
                                            product_name: productNameField.value,
                                            date:
                                                dateField.value.slice(0, 14) +
                                                '<small class="text-muted">' +
                                                dateField.value.slice(14, 22),
                                            amount: amountField.value,
                                            payment: paymentField.value,
                                            status: isStatus(statusField.value)
                                        });
                                    }
                                });

                                document.getElementById("close-modal").click();
                                clearFields();

                                Swal.fire({
                                    position: "center",
                                    icon: "success",
                                    title: "Order updated Successfully!",
                                    showConfirmButton: !1,
                                    timer: 2e3,
                                    showCloseButton: !0
                                });
                            }
                        } else {
                            // INSERTAR
                            orderList.add({
                                id:
                                    '<a href="apps-ecommerce-order-details.html" class="fw-medium link-primary">#VZ' +
                                    count +
                                    "</a>",
                                customer_name: customerNameField.value,
                                product_name: productNameField.value,
                                date: dateField.value,
                                amount: "$" + amountField.value,
                                payment: paymentField.value,
                                status: isStatus(statusField.value)
                            });

                            orderList.sort("id", { order: "desc" });
                            document.getElementById("close-modal").click();
                            clearFields();
                            refreshCallbacks();
                            filterOrder("All");
                            count++;

                            Swal.fire({
                                position: "center",
                                icon: "success",
                                title: "Order inserted successfully!",
                                showConfirmButton: !1,
                                timer: 2e3,
                                showCloseButton: !0
                            });
                        }
                    } else {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                },
                !1
            );
        }),
        new Choices(paymentField)
    ),
    statusVal = new Choices(statusField),
    productnameVal = new Choices(productNameField);

// Generar HTML de estatus
function isStatus(e) {
    switch (e) {
        case "Delivered":
            return (
                '<span class="badge bg-success-subtle text-success text-uppercase">' +
                e +
                "</span>"
            );
        case "Cancelled":
            return (
                '<span class="badge bg-danger-subtle text-danger text-uppercase">' +
                e +
                "</span>"
            );
        case "Inprogress":
            return (
                '<span class="badge bg-secondary-subtle text-secondary text-uppercase">' +
                e +
                "</span>"
            );
        case "Pickups":
            return (
                '<span class="badge bg-info-subtle text-info text-uppercase">' +
                e +
                "</span>"
            );
        case "Returns":
            return (
                '<span class="badge bg-primary-subtle text-primary text-uppercase">' +
                e +
                "</span>"
            );
        case "Pending":
            return (
                '<span class="badge bg-warning-subtle text-warning text-uppercase">' +
                e +
                "</span>"
            );
    }
}

// Manejo de checkbox por fila
function ischeckboxcheck() {
    Array.from(document.getElementsByName("checkAll")).forEach(function (a) {
        a.addEventListener("change", function (e) {
            if (1 == a.checked)
                e.target.closest("tr").classList.add("table-active");
            else
                e.target.closest("tr").classList.remove("table-active");

            var t = document.querySelectorAll('[name="checkAll"]:checked').length;

            document.getElementById("remove-actions").style.display =
                0 < t ? "block" : "none";
        });
    });
}

// Volver a enlazar eventos de editar/eliminar
function refreshCallbacks() {
    // Eliminar
    removeBtns &&
        Array.from(removeBtns).forEach(function (btn) {
            btn.addEventListener("click", function (e) {
                e.target.closest("tr").children[1].innerText;
                itemId = e.target.closest("tr").children[1].innerText;

                var items = orderList.get({ id: itemId });

                Array.from(items).forEach(function (item) {
                    deleteid = (new DOMParser).parseFromString(
                        item._values.id,
                        "text/html"
                    );
                    var t = deleteid.body.firstElementChild;

                    if (deleteid.body.firstElementChild.innerHTML == itemId) {
                        document
                            .getElementById("delete-record")
                            .addEventListener("click", function () {
                                orderList.remove("id", t.outerHTML);
                                document
                                    .getElementById("deleteRecord-close")
                                    .click();
                            });
                    }
                });
            });
        });

    // Editar
    editBtns &&
        Array.from(editBtns).forEach(function (btn) {
            btn.addEventListener("click", function (e) {
                e.target.closest("tr").children[1].innerText;
                itemId = e.target.closest("tr").children[1].innerText;

                var items = orderList.get({ id: itemId });

                Array.from(items).forEach(function (item) {
                    isid = (new DOMParser).parseFromString(
                        item._values.id,
                        "text/html"
                    );
                    var t = isid.body.firstElementChild.innerHTML;

                    if (t == itemId) {
                        editlist = !0;
                        idField.value = t;
                        customerNameField.value = item._values.customer_name;
                        productNameField.value = item._values.product_name;
                        dateField.value = item._values.date;
                        amountField.value = item._values.amount;

                        // Payment
                        example && example.destroy();
                        example = new Choices(paymentField, { searchEnabled: !1 });
                        var valPayment = item._values.payment;
                        example.setChoiceByValue(valPayment);

                        // Product name
                        productnameVal && productnameVal.destroy();
                        productnameVal = new Choices(productNameField, {
                            searchEnabled: !1
                        });
                        var valProduct = item._values.product_name;
                        productnameVal.setChoiceByValue(valProduct);

                        // Status
                        statusVal && statusVal.destroy();
                        statusVal = new Choices(statusField, { searchEnabled: !1 });
                        val = (new DOMParser).parseFromString(
                            item._values.status,
                            "text/html"
                        );
                        var valStatus = val.body.firstElementChild.innerHTML;
                        statusVal.setChoiceByValue(valStatus);

                        // Fecha con flatpickr
                        flatpickr("#date-field", {
                            enableTime: !0,
                            dateFormat: "d M, Y, h:i K",
                            defaultDate: item._values.date
                        });
                    }
                });
            });
        });
}

// Limpiar campos del modal
function clearFields() {
    customerNameField.value = "";
    productNameField.value = "";
    dateField.value = "";
    amountField.value = "";
    paymentField.value = "";

    example && example.destroy();
    example = new Choices(paymentField);

    productnameVal && productnameVal.destroy();
    productnameVal = new Choices(productNameField);

    statusVal && statusVal.destroy();
    statusVal = new Choices(statusField);
}

// Eliminar múltiple
function deleteMultiple() {
    ids_array = [];
    var e,
        t = document.querySelectorAll(".form-check [value=option1]");

    for (i = 0; i < t.length; i++)
        1 == t[i].checked &&
            (
                e = t[i].parentNode.parentNode.parentNode
                    .querySelector("td a")
                    .innerHTML,
                ids_array.push(e)
            );

    if ("undefined" != typeof ids_array && 0 < ids_array.length) {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: !0,
            confirmButtonClass: "btn btn-primary w-xs me-2 mt-2",
            cancelButtonClass: "btn btn-danger w-xs mt-2",
            confirmButtonText: "Yes, delete it!",
            buttonsStyling: !1,
            showCloseButton: !0
        }).then(function (e) {
            if (e.value) {
                for (i = 0; i < ids_array.length; i++)
                    orderList.remove(
                        "id",
                        '<a href="apps-ecommerce-order-details.html" class="fw-medium link-primary">' +
                        ids_array[i] +
                        "</a>"
                    );

                document.getElementById("remove-actions").style.display = "none";
                document.getElementById("checkAll").checked = !1;

                Swal.fire({
                    title: "Deleted!",
                    text: "Your data has been deleted.",
                    icon: "success",
                    confirmButtonClass: "btn btn-info w-xs mt-2",
                    buttonsStyling: !1
                });
            }
        });
    } else {
        Swal.fire({
            title: "Please select at least one checkbox",
            confirmButtonClass: "btn btn-info",
            buttonsStyling: !1,
            showCloseButton: !0
        });
    }
}

// Botones siguiente / anterior de paginación
document.querySelector(".pagination-next").addEventListener("click", function () {
    document.querySelector(".pagination.listjs-pagination") &&
        document
            .querySelector(".pagination.listjs-pagination")
            .querySelector(".active") &&
        document
            .querySelector(".pagination.listjs-pagination")
            .querySelector(".active")
            .nextElementSibling.children[0].click();
});

document.querySelector(".pagination-prev").addEventListener("click", function () {
    document.querySelector(".pagination.listjs-pagination") &&
        document
            .querySelector(".pagination.listjs-pagination")
            .querySelector(".active") &&
        document
            .querySelector(".pagination.listjs-pagination")
            .querySelector(".active")
            .previousSibling.children[0].click();
});
