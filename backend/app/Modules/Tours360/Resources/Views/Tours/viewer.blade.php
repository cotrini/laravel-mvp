<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#242424">
    <title>{{ $tour->title }} - Recorrido 360°</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
    <style>
        :root {
            color-scheme: dark;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #fff;
            background: #242424;
            font-synthesis: none;
            text-rendering: optimizeLegibility;
        }

        * { box-sizing: border-box; }

        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
            background: #242424;
        }

        #panorama {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }

        .viewer-topbar {
            position: fixed;
            z-index: 5;
            top: 16px;
            left: 16px;
            right: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            pointer-events: none;
        }

        .viewer-tour-card,
        .viewer-back-link {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            border: 1px solid rgb(255 255 255 / 18%);
            border-radius: 14px;
            background: rgb(26 31 32 / 76%);
            box-shadow: 0 8px 22px rgb(0 0 0 / 20%);
            color: #fff;
            backdrop-filter: blur(12px);
            pointer-events: auto;
        }

        .viewer-tour-card {
            max-width: min(520px, calc(100vw - 150px));
            padding: 10px 14px 10px 10px;
        }

        .viewer-brand-mark {
            width: 36px;
            height: 36px;
            display: grid;
            flex: none;
            place-items: center;
            border-radius: 11px;
            background: #ffe22d;
            color: #242424;
            font-size: 21px;
            font-weight: 850;
        }

        .viewer-tour-title {
            overflow: hidden;
            font-size: 13px;
            font-weight: 800;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .viewer-scene-title {
            margin-top: 3px;
            color: rgb(255 255 255 / 68%);
            font-size: 11px;
        }

        .viewer-back-link {
            min-height: 40px;
            padding: 0 14px;
            color: #ffe22d;
            font-size: 12px;
            font-weight: 750;
            text-decoration: none;
            white-space: nowrap;
        }

        .viewer-back-link:hover {
            background: #12877b;
            color: #fff;
        }

        .scene-nav {
            position: fixed;
            z-index: 5;
            right: 16px;
            bottom: 16px;
            left: 16px;
            display: flex;
            justify-content: center;
            gap: 9px;
            overflow-x: auto;
            padding: 10px;
            border: 1px solid rgb(255 255 255 / 17%);
            border-radius: 16px;
            background: rgb(26 31 32 / 78%);
            box-shadow: 0 8px 22px rgb(0 0 0 / 22%);
            backdrop-filter: blur(12px);
            scrollbar-width: thin;
            scrollbar-color: #12877b transparent;
        }

        .scene-btn {
            min-height: 38px;
            flex: none;
            padding: 0 15px;
            border: 1px solid rgb(255 255 255 / 16%);
            border-radius: 11px;
            background: rgb(255 255 255 / 8%);
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-size: 12px;
            font-weight: 700;
            transition: color 160ms ease, background 160ms ease, border-color 160ms ease;
        }

        .scene-btn:hover {
            border-color: #12877b;
            background: rgb(18 135 123 / 35%);
        }

        .scene-btn.active {
            border-color: #ffe22d;
            background: #ffe22d;
            color: #242424;
        }

        .pnlm-controls {
            border-radius: 12px !important;
            overflow: hidden;
            box-shadow: 0 4px 14px rgb(0 0 0 / 24%);
        }

        .pnlm-control {
            background-color: rgb(26 31 32 / 86%) !important;
        }

        @media (max-width: 560px) {
            .viewer-topbar {
                top: 10px;
                left: 10px;
                right: 10px;
                align-items: flex-start;
            }

            .viewer-tour-card {
                max-width: calc(100vw - 118px);
                gap: 8px;
                padding: 8px;
            }

            .viewer-brand-mark {
                width: 31px;
                height: 31px;
            }

            .viewer-tour-title { font-size: 11px; }
            .viewer-scene-title { font-size: 10px; }

            .viewer-back-link {
                min-height: 36px;
                padding: 0 10px;
                font-size: 11px;
            }

            .scene-nav {
                right: 10px;
                bottom: 10px;
                left: 10px;
                justify-content: flex-start;
                gap: 7px;
                padding: 8px;
            }

            .scene-btn {
                min-height: 35px;
                padding: 0 12px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div id="panorama" aria-label="Visor interactivo del recorrido 360°"></div>

    <header class="viewer-topbar">
        <div class="viewer-tour-card">
            <span class="viewer-brand-mark" aria-hidden="true">⌂</span>
            <div>
                <div class="viewer-tour-title">{{ $tour->title }}</div>
                <div class="viewer-scene-title" id="current-scene-title">{{ $scene->title }}</div>
            </div>
        </div>
        <a class="viewer-back-link" href="{{ route('tours360.show', $tour->slug) }}">Volver al recorrido</a>
    </header>

    <nav class="scene-nav" aria-label="Escenas del recorrido">
        @foreach($tour->scenes as $tourScene)
            <button
                id="btn-scene-{{ $tourScene->id }}"
                class="scene-btn {{ $tourScene->id === $scene->id ? 'active' : '' }}"
                type="button"
                data-scene-id="{{ $tourScene->id }}"
                data-scene-title="{{ $tourScene->title }}"
            >
                {{ $tourScene->title }}
            </button>
        @endforeach
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    <script>
        const viewer = pannellum.viewer('panorama', {
            default: {
                firstScene: 'scene_{{ $scene->id }}',
                author: 'Colombia Sin Comisión',
                autoLoad: true,
            },
            scenes: {
                @foreach($tour->scenes as $tourScene)
                    'scene_{{ $tourScene->id }}': {
                        title: {{ Illuminate\Support\Js::from($tourScene->title) }},
                        hfov: 110,
                        pitch: 0,
                        yaw: 0,
                        type: 'equirectangular',
                        panorama: {{ Illuminate\Support\Js::from($tourScene->image_url) }},
                        hotSpots: [
                            @foreach($tourScene->hotspots as $hotspot)
                                {
                                    pitch: {{ (float) $hotspot->pitch }},
                                    yaw: {{ (float) $hotspot->yaw }},
                                    type: 'scene',
                                    text: {{ Illuminate\Support\Js::from($hotspot->label) }},
                                    sceneId: 'scene_{{ $hotspot->target_scene_id }}',
                                },
                            @endforeach
                        ],
                    },
                @endforeach
            },
        });

        document.querySelectorAll('[data-scene-id]').forEach((button) => {
            button.addEventListener('click', () => {
                viewer.loadScene(`scene_${button.dataset.sceneId}`);
            });
        });

        viewer.on('scenechange', (sceneId) => {
            const activeSceneId = sceneId.replace('scene_', '');
            document.querySelectorAll('.scene-btn').forEach((button) => {
                button.classList.toggle('active', button.dataset.sceneId === activeSceneId);
                if (button.dataset.sceneId === activeSceneId) {
                    document.getElementById('current-scene-title').textContent = button.dataset.sceneTitle;
                }
            });
        });
    </script>
</body>
</html>
