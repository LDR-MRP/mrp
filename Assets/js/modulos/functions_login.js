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
              try {
                var objData = JSON.parse(request.responseText);
                if (objData.status) {
                  notifyToast("¡Acceso correcto! Redirigiendo...", "success", 1500);
                  setTimeout(function () {
                    window.location.href = base_url + "/dashboard";
                  }, 600);
                } else {
                  notifyToast(objData.msg || "Usuario o contraseña incorrecto.", "error");
                  document.querySelector("#txtPassword").value = "";
                }
              } catch (err) {
                console.error("Error procesando respuesta:", request.responseText);
                notifyToast("Error al procesar la respuesta del servidor.", "error");
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
              try {
                var objData = JSON.parse(request.responseText);
                if (objData.status) {
                  notifyToast(objData.msg, "success", 4000);
                  document.querySelector("#txtEmailReset").value = "";
                  setTimeout(function () {
                    if (typeof flipCard === "function") {
                      flipCard();
                    } else {
                      window.location = base_url + "/login";
                    }
                  }, 1800);
                } else {
                  notifyToast(objData.msg, "error");
                }
              } catch (err) {
                console.error("Error procesando respuesta:", request.responseText);
                notifyToast("Error en el servidor al solicitar restablecimiento.", "error");
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
              try {
                var objData = JSON.parse(request.responseText);
                if (objData.status) {
                  notifyToast(objData.msg || "Contraseña actualizada con éxito.", "success", 3000);
                  setTimeout(function () {
                    window.location = base_url + "/login";
                  }, 1500);
                } else {
                  notifyToast(objData.msg, "error");
                }
              } catch (err) {
                console.error("Error procesando respuesta:", request.responseText);
                notifyToast("Error en el servidor al actualizar la contraseña.", "error");
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
