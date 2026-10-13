{{-- The public tracking pages' look - shared by tracking and track_lookup. --}}
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f2f6f9;
            padding-bottom: 40px;
        }

        .track-bar {
            background: #1c1c1c;
            color: #fff;
            padding: 18px 0;
        }

        .track-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
            padding: 20px;
            margin-bottom: 16px;
        }

        /* The progress line sits behind the markers and is clipped by the
           first and last of them, so it never pokes out at either end. */
        .track-steps {
            display: flex;
            position: relative;
            margin-top: 22px;
        }

        .track-steps:before {
            content: '';
            position: absolute;
            top: 13px;
            left: 8%;
            right: 8%;
            height: 3px;
            background: #e4e9ee;
        }

        .track-progress {
            position: absolute;
            top: 13px;
            left: 8%;
            height: 3px;
            background: #9bcf1f;
            transition: width .4s ease;
        }

        .track-step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .track-dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #e4e9ee;
            color: #fff;
            margin: 0 auto 8px;
            line-height: 28px;
            font-size: 13px;
        }

        .track-step.done .track-dot {
            background: #9bcf1f;
        }

        .track-step-label {
            font-size: 11px;
            line-height: 1.3;
            color: #7b8794;
        }

        .track-step.done .track-step-label {
            color: #1c1c1c;
            font-weight: 500;
        }

        .track-status-pill {
            display: inline-block;
            background: #eaf5d3;
            color: #4d7c0f;
            border-radius: 20px;
            padding: 6px 16px;
            font-weight: 600;
        }

        .track-status-pill.stopped {
            background: #fde8e8;
            color: #b42318;
        }

        .track-label {
            font-size: 11px;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #7b8794;
        }

        .track-value {
            font-weight: 600;
            word-break: break-word;
        }
    </style>
