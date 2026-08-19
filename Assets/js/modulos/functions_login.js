const Toast = Swal.mixin({
  toast: true,
  position: "top-end",
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener("mouseenter", Swal.stopTimer);
    toast.addEventListener("mouseleave", Swal.resumeTimer);
  }
});


/**
 * ==========================================================
 * NOTIFICACIONES
 * ==========================================================
 */
function notifyToast(message, icon = "info", timer = 3000) {
  if (
    typeof Sys_Core !== "undefined" &&
    Sys_Core.UI &&
    typeof Sys_Core.UI.notify === "function"
  ) {
    Sys_Core.UI.notify(
      message,
      icon,
      "top-end",
      timer
    );

    return;
  }

  Toast.fire({
    icon: icon,
    title: message,
    timer: timer
  });
}


/**
 * ==========================================================
 * FLIP LOGIN / RECUPERAR CONTRASEÑA
 * ==========================================================
 *
 */
$(".login-content [data-toggle='flip']").click(function () {
  $(".login-box").toggleClass("flipped");
  return false;
});


/**
 * ==========================================================
 * LOADING
 * ==========================================================
 */
const divLoading = document.querySelector("#divLoading");


function mostrarLoading() {
  if (divLoading) {
    divLoading.style.display = "flex";
  }
}


function ocultarLoading() {
  if (divLoading) {
    divLoading.style.display = "none";
  }
}


/**
 * ==========================================================
 * PETICIÓN AJAX GENÉRICA
 * ==========================================================
 */
function enviarFormulario(url, formData, callback) {

  const request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");


  request.open(
    "POST",
    url,
    true
  );


  request.onreadystatechange = function () {

    if (request.readyState !== 4) {
      return;
    }


    ocultarLoading();


    if (request.status !== 200) {

      console.error(
        "Error HTTP:",
        request.status,
        request.responseText
      );


      notifyToast(
        "Ocurrió un error en el servidor.",
        "error"
      );

      return;
    }


    try {

      const objData =
        JSON.parse(
          request.responseText
        );


      callback(
        objData
      );


    } catch (error) {

      console.error(
        "Error procesando JSON:",
        request.responseText
      );


      notifyToast(
        "Error al procesar la respuesta del servidor.",
        "error"
      );
    }
  };


  request.onerror = function () {

    ocultarLoading();


    notifyToast(
      "No fue posible conectar con el servidor.",
      "error"
    );
  };


  request.send(
    formData
  );
}


/**
 * ==========================================================
 * DOM READY
 * ==========================================================
 */
document.addEventListener(
  "DOMContentLoaded",
  function () {


    /**
     * ======================================================
     * LOGIN TRADICIONAL
     * ======================================================
     *
     * IMPORANTE:
     *
     * Este login solamente se utiliza cuando:
     *
     * - no existe cookie SSO
     * - el token SSO está vencido
     * - el token SSO es inválido
     *
     * Si existe un token válido, el contrlador PHP redirige
     * automáticmente al dashboard antes de mostrar esta vista.
     */
    const formLogin =
      document.querySelector(
        "#formLogin"
      );


    if (formLogin) {

      formLogin.addEventListener(
        "submit",
        function (e) {

          e.preventDefault();


          const inputEmail =
            document.querySelector(
              "#txtEmail"
            );


          const inputPassword =
            document.querySelector(
              "#txtPassword"
            );


          if (
            !inputEmail ||
            !inputPassword
          ) {

            notifyToast(
              "No fue posible cargar el formulario de acceso.",
              "error"
            );

            return;
          }


          const strEmail =
            inputEmail.value.trim();


          const strPassword =
            inputPassword.value;


          /**
           * Validar campos
           */
          if (
            strEmail === "" ||
            strPassword === ""
          ) {

            notifyToast(
              "Introduce tus credenciales de acceso.",
              "warning"
            );

            return;
          }


          mostrarLoading();


          const ajaxUrl =
            base_url +
            "/Login/loginUser";


          const formData =
            new FormData(
              formLogin
            );


          enviarFormulario(
            ajaxUrl,
            formData,
            function (objData) {

              if (objData.status) {

                notifyToast(
                  "¡Acceso correcto! Redirigiendo...",
                  "success",
                  1500
                );


                setTimeout(
                  function () {

                    window.location.href =
                      base_url +
                      "/dashboard";

                  },
                  600
                );

                return;
              }


              notifyToast(
                objData.msg ||
                "Usuario o contraseña incorrectos.",
                "error"
              );


              inputPassword.value = "";

              inputPassword.focus();
            }
          );
        }
      );
    }


    /**
     * ======================================================
     * RECUPERAR CONTRASEÑA
     * ======================================================
     */
    const formRecetPass =
      document.querySelector(
        "#formRecetPass"
      );


    if (formRecetPass) {

      formRecetPass.addEventListener(
        "submit",
        function (e) {

          e.preventDefault();


          const inputEmailReset =
            document.querySelector(
              "#txtEmailReset"
            );


          if (!inputEmailReset) {

            notifyToast(
              "No fue posible cargar el formulario de recuperación.",
              "error"
            );

            return;
          }


          const strEmail =
            inputEmailReset.value.trim();


          if (strEmail === "") {

            notifyToast(
              "Escribe tu correo electrónico.",
              "warning"
            );

            return;
          }


          mostrarLoading();


          const ajaxUrl =
            base_url +
            "/Login/resetPass";


          const formData =
            new FormData(
              formRecetPass
            );


          enviarFormulario(
            ajaxUrl,
            formData,
            function (objData) {

              if (objData.status) {

                notifyToast(
                  objData.msg ||
                  "Solicitud procesada correctamente.",
                  "success",
                  4000
                );


                inputEmailReset.value =
                  "";


                setTimeout(
                  function () {

                    if (
                      typeof flipCard ===
                      "function"
                    ) {

                      flipCard();

                    } else {

                      window.location.href =
                        base_url +
                        "/login";
                    }

                  },
                  1800
                );


                return;
              }


              notifyToast(
                objData.msg ||
                "No fue posible realizar la recuperación.",
                "error"
              );
            }
          );
        }
      );
    }


    /**
     * ======================================================
     * CAMBIAR CONTRASEÑA
     * ======================================================
     */
    const formCambiarPass =
      document.querySelector(
        "#formCambiarPass"
      );


    if (formCambiarPass) {

      formCambiarPass.addEventListener(
        "submit",
        function (e) {

          e.preventDefault();


          const inputPassword =
            document.querySelector(
              "#txtPassword"
            );


          const inputPasswordConfirm =
            document.querySelector(
              "#txtPasswordConfirm"
            );


          if (
            !inputPassword ||
            !inputPasswordConfirm
          ) {

            notifyToast(
              "No fue posible cargar el formulario.",
              "error"
            );

            return;
          }


          const strPassword =
            inputPassword.value;


          const strPasswordConfirm =
            inputPasswordConfirm.value;


          /**
           * Campos vacíos
           */
          if (
            strPassword === "" ||
            strPasswordConfirm === ""
          ) {

            notifyToast(
              "Escribe la nueva contraseña.",
              "warning"
            );

            return;
          }


          /**
           * Longitud mínima
           */
          if (
            strPassword.length < 5
          ) {

            notifyToast(
              "La contraseña debe tener un mínimo de 5 caracteres.",
              "info"
            );

            return;
          }


          /**
           * Confirmación
           */
          if (
            strPassword !==
            strPasswordConfirm
          ) {

            notifyToast(
              "Las contraseñas no coinciden.",
              "error"
            );

            return;
          }


          mostrarLoading();


          const ajaxUrl =base_url +"/Login/setPassword";


          const formData =
            new FormData(
              formCambiarPass
            );


          enviarFormulario(
            ajaxUrl,
            formData,
            function (objData) {

              if (objData.status) {

                notifyToast(
                  objData.msg ||
                  "Contraseña actualizada con éxito.",
                  "success",
                  3000
                );


                setTimeout(
                  function () {

                    window.location.href =
                      base_url +
                      "/login";

                  },
                  1500
                );


                return;
              }


              notifyToast(
                objData.msg ||
                "No fue posible actualizar la contraseña.",
                "error"
              );
            }
          );
        }
      );
    }
  },
  false
);