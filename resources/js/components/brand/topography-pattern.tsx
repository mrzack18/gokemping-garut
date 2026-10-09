type TopographyPatternProps = {
    className?: string;
};

export default function TopographyPattern({
    className,
}: TopographyPatternProps) {
    return (
        <svg
            aria-hidden="true"
            className={className}
            viewBox="0 0 640 420"
            fill="none"
            preserveAspectRatio="xMidYMid slice"
        >
            <g stroke="currentColor" strokeWidth="1">
                <path d="M-60 78c74-50 124-55 182-18s90 40 149 4 112-48 177-8 105 37 205-16" />
                <path d="M-63 101c72-48 121-52 177-16s94 42 155 5 110-48 176-8 107 37 204-15" />
                <path d="M-66 125c70-46 118-50 173-14s98 43 161 5 108-48 175-8 109 36 203-15" />
                <path d="M-70 150c68-44 115-48 169-12s101 43 166 5 106-47 174-8 111 37 201-14" />
                <path d="M-75 177c66-42 112-45 164-10s105 43 171 5 104-47 173-8 113 36 199-14" />
                <path d="M-82 205c64-40 108-43 159-8s109 43 177 5 102-47 172-8 115 36 197-13" />
                <path d="M-90 235c61-38 104-41 154-6s113 43 183 5 100-46 171-8 117 35 194-13" />
                <path d="M-98 267c58-36 100-38 149-4s117 42 188 4 98-46 169-7 120 35 191-13" />
                <path d="M-108 301c55-33 95-35 143-2s121 41 193 4 95-46 167-7 122 34 188-12" />
                <path d="M-118 337c52-31 91-32 137 0s124 40 198 3 92-45 164-6 124 34 185-12" />
                <path d="M-128 375c49-28 86-29 131 2s128 39 202 2 89-44 162-5 126 33 182-11" />
            </g>
        </svg>
    );
}
