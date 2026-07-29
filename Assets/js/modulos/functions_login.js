const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer);
    toast.addEventListener('mouseleave', Swal.resumeTimer);
  }
});

function notifyToast(message, icon = 'info', timer = 3000) {
  if (typeof Sys_Core !== 'undefined' && Sys_Core.UI && Sys_Core.UI.notify) {
    Sys_Core.UI.notify(message, icon, 'top-end', timer);
  } else {
    Toast.fire({ icon: icon, title: message, timer: timer });
  }
}

$('.login-content [data-toggle="flip"]').click(function () {
  $(".login-box").toggleClass("flipped");
  return false;
});

var divLoading = document.querySelector("#divLoading");
document.addEventListener(
  "DOMContentLoaded",
  function () {
    if (document.querySelector("#formLogin")) {
      let formLogin = document.querySelector("#formLogin");
      formLogin.onsubmit = function (e) {
        e.preventDefault();

        let strEmail = document.querySelector("#txtEmail").value;
        let strPassword = document.querySelector("#txtPassword").value;

        if (strEmail == "" || strPassword == "") {
          notifyToast("Introduce tus credenciales de acceso", "warning");
          return false;
        } else {
          divLoading.style.display = "flex";
          var request = window.XMLHttpRequest
            ? new XMLHttpRequest()
            : new ActiveXObject("Microsoft.XMLHTTP");
          var ajaxUrl = base_url + "/Login/loginUser";
          var formData = new FormData(formLogin);
          request.open("POST", ajaxUrl, true);
          request.send(formData);
          request.onreadystatechange = function () {
            if (request.readyState != 4) return;
            if (request.status == 200) {
              var objData = JSON.parse(request.responseText);
              if (objData.status) {
                // --- PETICIÓN 2: API-DRIVEN (JWT) ---
                var requestApi = new XMLHttpRequest();
                var ajaxUrlApi = base_url + "/api/v1/login";

                requestApi.open("POST", ajaxUrlApi, true);
                requestApi.setRequestHeader("Content-Type", "application/json");

                requestApi.send(
                  JSON.stringify({
                    txtEmail: strEmail,
                    txtPassword: strPassword,
                  }),
                );

                requestApi.onreadystatechange = function () {
                  if (requestApi.readyState != 4) return;

                  if (requestApi.status == 200 || requestApi.status == 201) {
                    notifyToast("¡Acceso correcto! Redirigiendo...", "success", 1500);
                    setTimeout(function () {
                      window.location.reload(false);
                    }, 800);
                  } else {
                    notifyToast("Error al generar token de acceso API.", "error");
                    divLoading.style.display = "none";
                  }
                };
              } else {
                notifyToast(objData.msg || "Usuario o contraseña incorrecto.", "error");
                document.querySelector("#txtPassword").value = "";
              }
            } else {
              notifyToast("Error en el proceso de autenticación.", "error");
            }
            divLoading.style.display = "none";
            return false;
          };
        }
      };
    }

    if (document.querySelector("#formRecetPass")) {
      let formRecetPass = document.querySelector("#formRecetPass");
      formRecetPass.onsubmit = function (e) {
        e.preventDefault();

        let strEmail = document.querySelector("#txtEmailReset").value;
        if (strEmail == "") {
          notifyToast("Escribe tu correo electrónico.", "warning");
          return false;
        } else {
          divLoading.style.display = "flex";
          var request = window.XMLHttpRequest
            ? new XMLHttpRequest()
            : new ActiveXObject("Microsoft.XMLHTTP");

          var ajaxUrl = base_url + "/Login/resetPass";
          var formData = new FormData(formRecetPass);
          request.open("POST", ajaxUrl, true);
          request.send(formData);
          request.onreadystatechange = function () {
            if (request.readyState != 4) return;

            if (request.status == 200) {
              var objData = JSON.parse(request.responseText);
              if (objData.status) {
                notifyToast(objData.msg, "success");
                setTimeout(function () {
                  window.location = base_url;
                }, 2000);
              } else {
                notifyToast(objData.msg, "error");
              }
            } else {
              notifyToast("Error en el proceso de recuperación.", "error");
            }
            divLoading.style.display = "none";
            return false;
          };
        }
      };
    }

    if (document.querySelector("#formCambiarPass")) {
      let formCambiarPass = document.querySelector("#formCambiarPass");
      formCambiarPass.onsubmit = function (e) {
        e.preventDefault();

        let strPassword = document.querySelector("#txtPassword").value;
        let strPasswordConfirm = document.querySelector(
          "#txtPasswordConfirm",
        ).value;

        if (strPassword == "" || strPasswordConfirm == "") {
          notifyToast("Escribe la nueva contraseña.", "warning");
          return false;
        } else {
          if (strPassword.length < 5) {
            notifyToast("La contraseña debe tener un mínimo de 5 caracteres.", "info");
            return false;
          }
          if (strPassword != strPasswordConfirm) {
            notifyToast("Las contraseñas no coinciden.", "error");
            return false;
          }
          divLoading.style.display = "flex";
          var request = window.XMLHttpRequest
            ? new XMLHttpRequest()
            : new ActiveXObject("Microsoft.XMLHTTP");
          var ajaxUrl = base_url + "/Login/setPassword";
          var formData = new FormData(formCambiarPass);
          request.open("POST", ajaxUrl, true);
          request.send(formData);
          request.onreadystatechange = function () {
            if (request.readyState != 4) return;
            if (request.status == 200) {
              var objData = JSON.parse(request.responseText);
              if (objData.status) {
                notifyToast(objData.msg, "success");
                setTimeout(function () {
                  window.location = base_url + "/login";
                }, 2000);
              } else {
                notifyToast(objData.msg, "error");
              }
            } else {
              notifyToast("Error en el proceso.", "error");
            }
            divLoading.style.display = "none";
          };
        }
      };
    }
  },
  false,
);
