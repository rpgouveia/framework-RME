import type { SVGAttributes } from 'react';
import { useId } from 'react';

/**
 * The application's mark: a shield over a traceability chain, closed by a
 * verification check. Kept in sync with `public/favicon.svg`.
 *
 * The mark carries its own colors and tile, so it needs no `fill-current`
 * from the caller — only a size.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    const id = useId();
    const shieldGradient = `${id}-shield`;
    const checkGradient = `${id}-check`;

    return (
        <svg
            viewBox="0 0 512 512"
            xmlns="http://www.w3.org/2000/svg"
            {...props}
        >
            <defs>
                <linearGradient
                    id={shieldGradient}
                    x1="120"
                    y1="100"
                    x2="380"
                    y2="400"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop offset="0" stopColor="#36B8FF" />
                    <stop offset="1" stopColor="#245BFF" />
                </linearGradient>
                <linearGradient
                    id={checkGradient}
                    x1="300"
                    y1="300"
                    x2="420"
                    y2="430"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop offset="0" stopColor="#58F0C0" />
                    <stop offset="1" stopColor="#20C9A2" />
                </linearGradient>
            </defs>

            <rect width="512" height="512" rx="112" fill="#0B1426" />

            <path
                d="M256 75 L385 126 L385 250 C385 326 333 385 256 422 C179 385 127 326 127 250 L127 126 Z"
                fill="none"
                stroke={`url(#${shieldGradient})`}
                strokeWidth="19"
                strokeLinejoin="round"
            />

            <g
                fill="none"
                stroke="#F7FAFF"
                strokeWidth="13"
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <path d="M256 178 V317" />
                <path d="M256 278 L211 238" />
                <path d="M256 278 L301 238" />
                <path d="M211 238 V215" />
                <path d="M301 238 V215" />
                <path d="M211 238 L177 226" />
                <path d="M301 238 L335 226" />
            </g>

            <g fill="#F7FAFF">
                <circle cx="256" cy="162" r="19" />
                <circle cx="211" cy="199" r="17" />
                <circle cx="301" cy="199" r="17" />
                <circle cx="165" cy="221" r="17" />
                <circle cx="347" cy="221" r="17" />
            </g>

            <circle cx="365" cy="355" r="76" fill="#0B1426" />
            <circle
                cx="365"
                cy="355"
                r="65"
                fill="none"
                stroke={`url(#${checkGradient})`}
                strokeWidth="17"
                strokeDasharray="340 75"
                strokeDashoffset="20"
                strokeLinecap="round"
                transform="rotate(-45 365 355)"
            />
            <path
                d="M330 355 L353 378 L401 326"
                fill="none"
                stroke={`url(#${checkGradient})`}
                strokeWidth="18"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
