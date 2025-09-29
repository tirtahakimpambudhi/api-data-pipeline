import { useEffect, useState } from 'react'
import { toast } from 'sonner'

type Flash = {
    message?: string
    success?: string
    error?: string
}

export const useFlash = (value: Flash | undefined) => {
    const [errorFlash, setErrorFlash] = useState<string | undefined>(undefined)
    const [successFlash, setSuccessFlash] = useState<string | undefined>(undefined)
    const [messageFlash, setMessageFlash] = useState<string | undefined>(undefined)

    const resetAll = () => {
        setErrorFlash(undefined)
        setSuccessFlash(undefined)
        setMessageFlash(undefined)
    }

    useEffect(() => {
        if (!value) return

        if (value.error) {
            toast.error(value.error)
            resetAll()
            return
        }
        if (value.success) {
            toast.success(value.success)
            resetAll()
            return
        }
        if (value.message) {
            toast.info(value.message)
            resetAll()
            return
        }
    }, [value?.error, value?.success, value?.message])

    useEffect(() => {
        if (value) return
        if (errorFlash) toast.error(errorFlash)
    }, [errorFlash, value])

    useEffect(() => {
        if (value) return
        if (successFlash) toast.success(successFlash)
    }, [successFlash, value])

    useEffect(() => {
        if (value) return
        if (messageFlash) toast.info(messageFlash)
    }, [messageFlash, value])

    return {
        errorFlash,
        successFlash,
        messageFlash,
        resetAll,
        setErrorFlash,
        setSuccessFlash,
        setMessageFlash,
    }
}
