import { Modal } from './Modal';
import { Button } from './Button';
import { useLocaleStore } from '@/store/locale';

interface ConfirmDialogProps {
  open: boolean;
  title: string;
  message: string;
  confirmLabel?: string;
  loading?: boolean;
  onConfirm: () => void;
  onCancel: () => void;
}

export function ConfirmDialog({
  open,
  title,
  message,
  confirmLabel,
  loading,
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
  const t = useLocaleStore((s) => s.t);
  return (
    <Modal
      open={open}
      title={title}
      onClose={onCancel}
      size="md"
      footer={
        <>
          <Button variant="secondary" onClick={onCancel} disabled={loading}>
            {t('cancel')}
          </Button>
          <Button variant="danger" onClick={onConfirm} loading={loading}>
            {confirmLabel ?? t('confirm')}
          </Button>
        </>
      }
    >
      <p className="text-sm text-slate-600 dark:text-slate-300">{message}</p>
    </Modal>
  );
}
