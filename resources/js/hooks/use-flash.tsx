import { useEffect, useState } from 'react';
import { toast } from 'sonner';

type Flash = {
    message?: string;
    success?: string;
    error?: string;
}

export const useFlash = (value : Flash | undefined) => {
    const [errorFlash, setErrorFlash] = useState(value?.error);
    const [successFlash, setSuccessFlash] = useState(value?.success);
    const [messageFlash, setMessageFlash] = useState(value?.message);

    const resetAll = () => {
        setErrorFlash(undefined);
        setSuccessFlash(undefined);
        setMessageFlash(undefined);
    }

    useEffect(() => {
        if (errorFlash) toast.error(errorFlash);
    }, [errorFlash]);

    useEffect(() => {
        if (successFlash) toast.success(successFlash);
    }, [successFlash]);

    useEffect(() => {
        if (messageFlash) toast.info(messageFlash);
    }, [messageFlash]);


    return {
      errorFlash,
      successFlash,
      messageFlash,
      resetAll
    };
}
