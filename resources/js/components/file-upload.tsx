import { FileText, Upload, X } from 'lucide-react';
import { useCallback, useState, type DragEvent } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type FileUploadProps = {
    files: File[];
    onChange: (files: File[]) => void;
    maxFiles?: number;
    accept?: string;
    error?: string;
};

export function FileUpload({
    files,
    onChange,
    maxFiles,
    accept,
    error,
}: FileUploadProps) {
    const [isDragging, setIsDragging] = useState(false);

    const handleFiles = useCallback(
        (newFiles: FileList | null) => {
            if (!newFiles) {
                return;
            }

            const validFiles = Array.from(newFiles);

            const combined = maxFiles
                ? [...files, ...validFiles].slice(0, maxFiles)
                : [...files, ...validFiles];
            onChange(combined);
        },
        [files, maxFiles, onChange],
    );

    const handleDrop = useCallback(
        (e: DragEvent) => {
            e.preventDefault();
            setIsDragging(false);
            handleFiles(e.dataTransfer.files);
        },
        [handleFiles],
    );

    const removeFile = (index: number) => {
        const updated = files.filter((_, i) => i !== index);
        onChange(updated);
    };

    return (
        <div className="space-y-3">
            <div
                onDragOver={(e) => {
                    e.preventDefault();
                    setIsDragging(true);
                }}
                onDragLeave={() => setIsDragging(false)}
                onDrop={handleDrop}
                className={cn(
                    'flex flex-col items-center justify-center rounded-lg border-2 border-dashed px-6 py-8 transition-colors',
                    isDragging
                        ? 'border-primary bg-primary/5'
                        : 'border-gray-300 dark:border-gray-600',
                    maxFiles && files.length >= maxFiles && 'pointer-events-none opacity-50',
                )}
            >
                <Upload className="mb-2 size-8 text-gray-400" />
                <p className="text-sm text-gray-600 dark:text-gray-400">
                    Arrastra archivos aquí o{' '}
                    <label className="cursor-pointer font-medium text-primary hover:underline">
                        selecciona
                        <input
                            type="file"
                            className="hidden"
                            accept={accept}
                            multiple
                            onChange={(e) => handleFiles(e.target.files)}
                            disabled={maxFiles ? files.length >= maxFiles : false}
                        />
                    </label>
                </p>
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-500">
                    Máximo 10MB por archivo
                </p>
            </div>

            {files.length > 0 && (
                <ul className="space-y-2">
                    {files.map((file, index) => (
                        <li
                            key={`${file.name}-${index}`}
                            className="flex items-center gap-3 rounded-md border border-gray-200 px-3 py-2 dark:border-gray-700"
                        >
                            <FileText className="size-4 shrink-0 text-gray-400" />
                            <span className="min-w-0 flex-1 truncate text-sm text-gray-700 dark:text-gray-300">
                                {file.name}
                            </span>
                            <span className="text-xs text-gray-500">
                                {(file.size / 1024).toFixed(0)} KB
                            </span>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="size-6"
                                onClick={() => removeFile(index)}
                            >
                                <X className="size-3" />
                            </Button>
                        </li>
                    ))}
                </ul>
            )}

            {error && (
                <p className="text-sm text-red-600 dark:text-red-400">
                    {error}
                </p>
            )}
        </div>
    );
}
