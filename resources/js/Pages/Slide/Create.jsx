import { useEffect } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import TextArea from '@/Components/TextArea';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create() {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
    });

    useEffect(() => {
        return () => {
            reset('name', 'description');
        };
    }, []);

    const submit = (e) => {
        e.preventDefault();

        post(route('google.slides.create'));
    };

    return (
        <GuestLayout>
            <Head title="Create Google slide" />

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="title" value="Title" />

                    <TextInput
                        id="title"
                        name="title"
                        value={data.title}
                        className="mt-1 block w-full"
                        autoComplete="name"
                        isFocused={true}
                        onChange={(e) => setData('title', e.target.value)}
                        required
                    />

                    <InputError message={errors.title} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="description" value="Description" />

                    <TextArea
                        id="description"
                        type="description"
                        name="description"
                        value={data.description}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        onChange={(e) => setData('description', e.target.value)}
                        required
                    />

                    <InputError message={errors.description} className="mt-2" />
                </div>


                <div className="flex items-center justify-end mt-4">
                  
                    <PrimaryButton className="ml-4" disabled={processing}>
                        Create
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
