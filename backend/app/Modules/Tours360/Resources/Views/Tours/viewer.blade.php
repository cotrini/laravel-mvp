<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tour->title }} - Visor 360</title>
    
    <!-- Pannellum CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
    
    <style>
        body, html {
            margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden; background-color: #000; font-family: sans-serif;
        }
        #panorama { width: 100%; height: 100%; }
        
        /* Estilo flotante para la lista de escenas en la parte inferior */
        .scene-nav {
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
            display: flex; gap: 10px; background: rgba(0, 0, 0, 0.7);
            padding: 10px 15px; border-radius: 30px; z-index: 999; backdrop-filter: blur(5px);
        }
        .scene-btn {
            background: #1e293b; color: #fff; border: 1px solid #475569;
            padding: 6px 14px; border-radius: 20px; cursor: pointer; font-size: 13px; transition: all 0.2s;
        }
        .scene-btn:hover, .scene-btn.active { background: #0ea5e9; border-color: #38bdf8; }
    </style>
</head>
<body>

    <!-- Contenedor del visor -->
    <div id="panorama"></div>

<!-- Barra de navegación inferior -->
    <div class="scene-nav">
        @foreach($tour->scenes as $s)
            {{-- Añadimos un id único a cada botón para poder controlarlos con JS --}}
            <button id="btn-scene-{{ $s->id }}" 
                    class="scene-btn {{ $s->id == $scene->id ? 'active' : '' }}" 
                    onclick="cambiarEscena('{{ $s->id }}')">
                {{ $s->title }}
            </button>
        @endforeach
    </div>

    <!-- Pannellum JS -->
    <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    
    <script>
        let viewer = pannellum.viewer('panorama', {
            "default": {
                "firstScene": "scene_{{ $scene->id }}",
                "author": "Colombia Sin Comisión",
                "autoLoad": true
            },
            "scenes": {
                @foreach($tour->scenes as $s)
                "scene_{{ $s->id }}": {
                    "title": "{{ $s->title }}",
                    "hfov": 110,
                    "pitch": 0,
                    "yaw": 0,
                    "type": "equirectangular",
                    "panorama": "{{ $s->image_url }}",
                    "hotSpots": [
                        @foreach($s->hotspots as $hotspot)
                        {
                            "pitch": {{ (float) $hotspot->pitch }},
                            "yaw": {{ (float) $hotspot->yaw }},
                            "type": "scene",
                            "text": "{{ $hotspot->label }}",
                            "sceneId": "scene_{{ $hotspot->target_scene_id }}"
                        },
                        @endforeach
                    ]
                },
                @endforeach
            }
        });

        // Función al hacer clic en los botones de abajo
        function cambiarEscena(sceneId) {
            viewer.loadScene('scene_' + sceneId);
        }

        // Escucha automática cuando Pannellum cambia de escena (por botón o por hotspot)
        viewer.on('scenechange', function(sceneId) {
            // Limpiamos el prefijo 'scene_' para obtener el ID real
            let rawId = sceneId.replace('scene_', '');
            
            // Quitamos la clase 'active' de todos los botones
            document.querySelectorAll('.scene-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Activamos únicamente el botón de la escena actual
            let activeBtn = document.getElementById('btn-scene-' + rawId);
            if (activeBtn) {
                activeBtn.classList.add('active');
            }
        });
    </script>
</body>
</html>