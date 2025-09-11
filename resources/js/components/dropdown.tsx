import { Menu, Transition } from '@headlessui/react';
import { Link } from '@inertiajs/react';
import { Fragment, ReactNode } from 'react';

interface DropdownProps {
    trigger: ReactNode;
    children: ReactNode;
}

export function Dropdown({ trigger, children }: DropdownProps) {
    return (
        <Menu as="div" className="relative inline-block text-left">
            <div>
                <Menu.Button as="div">{trigger}</Menu.Button>
            </div>
            <Transition
                as={Fragment}
                enter="transition ease-out duration-100"
                enterFrom="transform opacity-0 scale-95"
                enterTo="transform opacity-100 scale-100"
                leave="transition ease-in duration-75"
                leaveFrom="transform opacity-100 scale-100"
                leaveTo="transform opacity-0 scale-95"
            >
                <Menu.Items className="ring-opacity-5 absolute right-0 z-10 mt-2 w-48 origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black focus:outline-none">
                    <div className="px-1 py-1">{children}</div>
                </Menu.Items>
            </Transition>
        </Menu>
    );
}

export function DropdownLink({ href, method = 'get', as = 'a', children, ...props }: any) {
    return (
        <Menu.Item>
            {({ active }) => (
                <Link
                    {...props}
                    href={href}
                    method={method}
                    as={as}
                    className={`${
                        active ? 'bg-gray-100 text-gray-900' : 'text-gray-700'
                    } group flex w-full items-center rounded-md px-2 py-2 text-sm`}
                >
                    {children}
                </Link>
            )}
        </Menu.Item>
    );
}
