---
description: Regla para usar artefactos en lugar del chat para planes, revisiones y análisis extensos.
trigger: always_on
---

# Uso de Artefactos (Artifacts) para Planes y Revisiones

Cuando presentes un plan de implementación, una revisión de código, un análisis de arquitectura o cualquier bloque extenso de información estructurada (como una lista de correcciones, pasos o análisis de datos), **DEBES usar el sistema de artefactos (creando archivos .md)** en el directorio temporal o definido por el sistema de brain del agente.

**NO pongas planes de implementación extensos ni correcciones de código directamente en el chat.**

**Instrucciones:**
1. Genera archivos como `plan_de_implementacion.md`, `code_review.md` usando la herramienta `write_to_file` en el directorio de artefactos asignado.
2. En tu respuesta de chat, simplemente menciona qué artefactos has creado con enlaces cliqueables y resume brevemente la intención.
3. El chat debe mantenerse limpio, conciso y directo, orientado únicamente a discutir y compartir el link al artefacto.
